(function (Drupal, drupalSettings) {
  let suspend = false;

  Drupal.behaviors.processDisplay = {
    attach: function (context, settings) {
      // Add event listeners to the create button.
      let createButton = document.getElementById('create-new-process');
      if (createButton) {
        createButton.addEventListener('click', createNewProcess);
      }
      // Add event listeners to the check button.
      let continueButton = document.getElementById('continue-process');
      if (continueButton) {
        continueButton.addEventListener('click', continueProcess);
      }
    }
  };

  initKamihayaAi = function() {
    let headerTabs = document.querySelectorAll('.results-block-header-item a');
    if (headerTabs) {
      for (let i = 0; i < headerTabs.length; i++) {
        headerTabs[i].addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          let tab = this.getAttribute('href');
          if (tab) {
            tab = tab.replace('#', '');
          }
          let step = tab.replace('step-body-', '');
          // Switch header tabs ad result body.
          switchResultBlock(step);
        });
      }
    }

    let stopButtons = document.querySelectorAll('.btn-stop');
    if (stopButtons) {
      for (let i = 0; i < stopButtons.length; i++) {
        stopButtons[i].addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          processSuspend();
        });
      }
    }

    let processBlock = document.getElementsByClassName('process-block')[0];
    if (processBlock) {
      // Add event listeners to the process image.
      let processBlockItems = processBlock.getElementsByClassName('process-block-item');
      if (processBlockItems) {
        for (let i = 0; i < processBlockItems.length; i++) {
          let image = processBlockItems[i].getElementsByTagName('img')[0];
          if (image) {
            image.addEventListener('click', function (event) {
              processImageClick(event);
            });
          }
        }
      }
    }
  }

  // Ajax request function.
  sendAjaxRequest = function(url, data = {}, callback = null, errorCallback = null) {
    let options = { method: 'POST' };

    // Set request headers and body.
    if (data instanceof FormData) {
      options.body = data; // FormData object.
    } else {
      options.headers = { 'Content-Type': 'application/json' };
      options.body = JSON.stringify(data);
    }

    // Execute fetch request.
    fetch(url, options)
      .then(response => response.json())
      .then(result => {
        if (suspend) {
          return;
        }
        if (result.status !== 'success') {
          throw new Error(result.message);
        }
        if (callback) callback(result);
      })
      .catch(error => {
        if (errorCallback) {
          errorCallback(error);
        } else {
          console.error('Ajax Error:', error);
        }
      });
  }

  // Execute step function.
  executeStep = function (step, data, responseKey, chatMessage, nextCallback = null, promptKey = null) {

    // Hide the main block.
    let mainBlock = document.getElementsByClassName('chat-block-body-content-main')[0];
    if (mainBlock && !mainBlock.classList.contains('hidden')) {
      mainBlock.classList.remove('glassmorphism');
      mainBlock.classList.add('hidden');
    }

    // Set the current step.
    let stepTag = document.getElementById('current-step');
    if (stepTag) {
      stepTag.value = step;
    }

    // Add message to chat.
    if (chatMessage.length > 0) {
      addChatMessages(chatMessage, 'proceed', 0);
    }

    // Change the main container to grid.
    let mainContainer = document.getElementsByClassName('kamihaya-ai-container-main')[0];
    let processBlock = document.getElementsByClassName('process-block')[0];
    if (mainContainer && !mainContainer.classList.contains('grid')) {
      mainContainer.classList.add('grid');
      if (processBlock) {
        mainContainer.classList.add('process');
      }
    }

    // Minimize the process block.
    if (processBlock && !processBlock.classList.contains('minimized') && !processBlock.classList.contains('maximized')) {
      processBlock.classList.add('minimized');
    }

    // Display the result header.
    let resultHeader = document.getElementsByClassName('results-block-header-container')[0];
    if (resultHeader && resultHeader.classList.contains('hidden')) {
      resultHeader.classList.remove('hidden');
    }

    // Display result body.
    let resultBody = document.getElementsByClassName('results-block')[0];
    if (resultBody && resultBody.classList.contains('results-block--hidden')) {
      resultBody.classList.add('maximized');
      resultBody.classList.remove('results-block--hidden');
    }

    // Dispaly the swicher.
    let switcher = document.getElementsByClassName('process-result-switcher')[0];
    if (processBlock && switcher && switcher.classList.contains('hidden')) {
      switcher.classList.remove('hidden');
      let links = switcher.getElementsByTagName('a');
      if (links && links.length > 0) {
        for (let i = 0; i < links.length; i++) {
          links[i].addEventListener('click', switchBlocks);
        }
      }
    }

    // Deactivate the previous header tab.
    let previousHeaderTab = document.querySelector('.results-block-header-item.active');
    if (previousHeaderTab) {
      previousHeaderTab.classList.remove('active');
    }

    // Activate the header tab.
    let headerTab = document.querySelector('.results-block-header-item.step-' + step.replace('_', '-'));
    if (headerTab) {
      let link = headerTab.querySelector('a');
      if (link) {
        link.classList.remove('disabled');
      }
      headerTab.classList.add('running', 'active');
    }

    // Switch the process block.
    switchProcess(step);

    // Deactivate the previous result body.
    let previousResultBody = document.querySelector('.results-block-body-item.active');
    if (previousResultBody) {
      previousResultBody.classList.remove('active');
    }

    // Activate the result body.
    let resultBodyItem = document.getElementById('step-body-' + step.replace('_', '-'));
    if (resultBodyItem) {
      resultBodyItem.classList.add('active');
    }

    // Play the loading animation.
    let loadingAnimation = document.querySelector('#step-body-' + step.replace('_', '-') + ' video');
    if (loadingAnimation) {
      loadingAnimation.play();
    }

    // Add step to data.
    data.step = step;

    // Send the ajax request.
    sendAjaxRequest(Drupal.url(drupalSettings.ajax_url), data, function (response) {
      ajaxSuccess(response, step, responseKey, loadingAnimation, headerTab, promptKey);
      if (nextCallback) {
        nextCallback();
      }
      else {
        // Switch the process block.
        switchProcess('complete');
      }
    }, function (error) {
      ajaxError(error, step, loadingAnimation, headerTab);
    })
  };

  // Ajax success handling function.
  ajaxSuccess = function (response, step, responseKey, loadingAnimation, headerTab, promptKey = null) {
    // Stop the loading animation.
    if (loadingAnimation) {
      loadingAnimation.pause();
    }

    // Change the class of header tab.
    if (headerTab) {
      headerTab.classList.remove('running');
      headerTab.classList.add('finished');
    }

    // Remove the animation tag.
    let animationTag = document.querySelector('#step-body-' + step.replace('_', '-') + ' .results-block-body-item-movie');
    if (animationTag) {
      animationTag.remove();
    }

    if (response.message) {
      // Add message to chat.
      addChatMessages([response.message], 'info', 0);
    }
    // Display the result.
    if (response[responseKey] !== undefined) {
      let result = response[responseKey];
      let resultBlock = document.getElementById('step-body-' + step.replace('_', '-'));

      let promptHTML = '';
      // Display the prompt.
      if (response[promptKey] !== undefined) {
        let prompt = response[promptKey];
        promptHTML = '<div class="prompt-container"><div class="prompt-title">' + Drupal.t('Prompt') + '</div>';
        promptHTML += '<div class="prompt ' + responseKey.replace('_', '-') + '">' + prompt + '</div></div>';
      }
      resultBlock.innerHTML = '<div class="result ' + responseKey.replace('_', '-') + '">' + result + '</div>' + promptHTML;
    }

  };

  // Error handling function.
  ajaxError = function(error, step, loadingAnimation, headerTab) {
    // Stop the loading animation.
    if (loadingAnimation) {
      loadingAnimation.pause();
    }

    // Change the class of header tab.
    if (headerTab) {
      headerTab.classList.remove('running');
      headerTab.classList.add('failed');
    }

    // Remove the animation tag.
    let animationTag = document.querySelector('#step-body-' + step + ' .results-block-body-item-movie');
    if (animationTag) {
      animationTag.remove();
    }

    if (error) {
      console.error('Ajax Error:', error);
      // Add message to chat.
      addChatMessages([error], 'error', 0);

      // Display the error to body.
      let resultBlock = document.getElementById('step-body-' + step.replace('_', '-'));
      resultBlock.innerHTML = '<div class="result error">' + error + '</div>';
    }
  }

  // Suspend handling function.
  processSuspend = function() {
    // If the process is already suspended, return.
    if (suspend) return;

    // Get the current step.
    let stepTag = document.getElementById('current-step');
    let step;
    if (stepTag) {
      step = stepTag.value;
    }

    if (!step) return;

    // Stop the loading animation.
    let loadingAnimation = document.querySelector('#step-body-' + step.replace('_', '-') + ' video');
    if (loadingAnimation) {
      loadingAnimation.pause();
    }

    // Change the class of header tab.
    let headerTab = document.querySelector('.results-block-header-item.step-' + step.replace('_', '-'));
    if (headerTab) {
      headerTab.classList.remove('running');
      headerTab.classList.add('suspended');
    }

    // Remove the animation tag.
    let animationTag = document.querySelector('#step-body-' + step + ' .results-block-body-item-movie');
    if (animationTag) {
      animationTag.remove();
    }

    // Add message to chat.
    addChatMessages([Drupal.t('The process has been suspended.')], 'proceed', 0);

    // Display the error to body.
    let resultBlock = document.getElementById('step-body-' + step.replace('_', '-'));
    resultBlock.innerHTML = '<div class="result suspended">' + Drupal.t("The result isn't created because the process has been suspended."); + '</div>';

    // Set the suspend flag.
    suspend = true;
  }

  // Create new process function.
  createNewProcess = function(event) {
    event.preventDefault();
    event.stopPropagation();

    // Hide action buttons.
    let actionButtons = document.querySelector('.chat-block-body-process-form .form-actions');
    if (actionButtons) {
      actionButtons.classList.add('hidden');
    }

    // Add message to chat.
    let messages = [
      Drupal.t("You selected 'Create'."),
      Drupal.t('Please wait while we design a new process for @type.', { '@type': drupalSettings.process_name }),
      Drupal.t('The design proceeding...'),
      Drupal.t('The design for @type is completed.', { '@type': drupalSettings.process_name }),
      Drupal.t("I’ll assist you with @type step.", { '@type': drupalSettings.task_name }),
    ];
    addChatMessages(messages.slice(0, 3), 'proceed');

    // Display process.
    setTimeout(function() {
      // Display the new process.
      switchProcess('new');

      let process = document.getElementsByClassName('process-block')[0];
      if (process && process.classList.contains('process-block--hidden')) {
        // Add the back to top button
        let backToTop = document.createElement('div');
        backToTop.className = 'back-to-top';
        let backToTopLink = document.createElement('a');
        backToTopLink.href = '#top';
        backToTopLink.className = 'back-to-top-link btn btn-primary button--small';
        backToTopLink.innerHTML = Drupal.t('Back to top');
        backToTopLink.addEventListener('click', function(event) {
          event.preventDefault();
          event.stopPropagation();
          // Reload the page.
          window.location.reload();
        });
        backToTop.appendChild(backToTopLink);
        process.appendChild(backToTop);
        // Display the process block.
        process.classList.remove('process-block--hidden');
      }

      setTimeout(function() {
        // Add message to chat.
        addChatMessages(messages.slice(3), 'proceed');
        // Display the task process.
        switchProcess('task');
      }, 2000);
    }, messages.length * 1000);

  }

  // Continue process function.
  continueProcess = function(event) {
    event.preventDefault();
    event.stopPropagation();

    // Hide action buttons.
    let actionButtons = document.querySelector('.chat-block-body-process-form .form-actions');
    if (actionButtons) {
      actionButtons.classList.add('hidden');
    }

    // Add message to chat.
    let messages = [
      Drupal.t("You selected 'Resume'."),
      Drupal.t('Please confirm the process.'),
      Drupal.t('Last time, we completed everything up to @type.', { '@type': drupalSettings.last_task_name }),
      Drupal.t('The next step is to @type.', { '@type': drupalSettings.task_name }),
      Drupal.t("I’ll assist you with @type step.", { '@type': drupalSettings.task_name }),
    ];
    addChatMessages(messages.slice(0, 2), 'proceed');

    // Display process.
    setTimeout(function () {
      // Display the start process.
      switchProcess('start');

      // Display the process block.
      let process = document.getElementsByClassName('process-block')[0];
      if (process && process.classList.contains('process-block--hidden')) {
        process.classList.remove('process-block--hidden');
      }

    }, 3000);

    setTimeout(function () {
      // Add message to chat.
      addChatMessages(messages.slice(2), 'proceed', 0);
    }, 4000);

    setTimeout(function () {
      // Remove the labels in the main block.
      let mainBlock = document.getElementsByClassName('chat-block-body-content-main')[0];
      if (mainBlock) {
        let labels = mainBlock.getElementsByTagName('p');
        for (let i = 0; i < labels.length; i++) {
          labels[i].remove();
          i--;
        }
        // Change the chat status.
        addChatMessages([], 'info', 0);
        // Display the main block.
        mainBlock.classList.remove('hidden');
        mainBlock.classList.add('glassmorphism');
        mainBlock.classList.add('proceed');

        // Scroll donw the chat block.
        let chatBlockScroll = document.getElementsByClassName('chat-block-body-scroll')[0];
        if (chatBlockScroll) {
          chatBlockScroll.scrollTo({
            top: chatBlockScroll.scrollHeight,
            behavior: 'smooth'
          });
        }
      }

    }, 5000);
  }

  // Switch process function.
  switchProcess = function(process) {
    // Hide the current process.
    let currentProcess = document.getElementsByClassName('process-block-item active')[0];
    if (currentProcess) {
      currentProcess.classList.remove('active');
      currentProcess.classList.add('hidden');
    }

    // Show the selected process.
    let newProcess = document.getElementById('process-' + process.replace('_', '-'));
    if (newProcess && newProcess.classList.contains('hidden')) {
      newProcess.classList.remove('hidden');
      newProcess.classList.add('active');
    }
  }

  // Add chat messages function.
  addChatMessages = function(messages, status = 'info', interval = 1000) {
    let chatBlock = document.getElementsByClassName('chat-block-body-content')[0];
    if (chatBlock) {
      // Change proceed messages to info messages.
      let proceedsMessages = chatBlock.getElementsByClassName('chat-block-body-item--proceed');
      if (proceedsMessages.length > 0) {
        for (let i = 0; i < proceedsMessages.length; i++) {
          proceedsMessages[i].classList.add('chat-block-body-item--info');
          proceedsMessages[i].classList.remove('chat-block-body-item--proceed');
          i--;
        }
      }

      if (messages.length === 0) {
        return;
      }
      // Add new messages.
      for (let i = 0; i < messages.length; i++) {
        setTimeout(function () {
          let child = document.createElement('div');
          child.className = 'chat-block-body-item chat-block-body-item--' + status;
          child.innerHTML = messages[i];
          chatBlock.appendChild(child);

          // Scroll donw the chat block.
          let chatBlockScroll = document.getElementsByClassName('chat-block-body-scroll')[0];
          if (chatBlockScroll) {
            chatBlockScroll.scrollTo({
              top: chatBlockScroll.scrollHeight,
              behavior: 'smooth'
            });
          }
        }, i * interval);
      }
    }
  }

  // Resize observer to scroll down the chat block.
  let chatBlockScroll = document.getElementsByClassName('chat-block-body-scroll')[0];
  let chatContent = document.getElementsByClassName('chat-block-body-content-main')[0];

  if (chatBlockScroll && chatContent) {
    const resizeObserver = new ResizeObserver(() => {
      // Scroll donw the chat block.
      chatBlockScroll.scrollTo({
        top: chatBlockScroll.scrollHeight,
        behavior: 'smooth'
      });
    });

    // Observe the chat block.
    resizeObserver.observe(chatBlockScroll);

    // Oberver for the chat content.
    const mutationObserver = new MutationObserver(() => {
      chatBlockScroll.scrollTo({
        top: chatBlockScroll.scrollHeight,
        behavior: 'smooth'
      });
    });

    // Observe the chat content.
    mutationObserver.observe(chatContent, {
      childList: true,
      subtree: true
    });
  }

  // Switch blocks function.
  switchBlocks = function(event) {
    event.preventDefault();
    event.stopPropagation();

    let target = event.target;

    if (target.tagName !== 'A') {
      target = target.closest('a');
    }
    let href = target.getAttribute('href');
    // Remove the hash from the href.
    href = href.replace('#', '');
    // Check if the href is empty.
    if (href === '') return;

    // Get the container.
    let container = document.getElementsByClassName('kamihaya-ai-container-main')[0];
    if (!container) return;

    // Get the maximized block.
    let maximizedBlock = container.getElementsByClassName('maximized')[0];
    if (!maximizedBlock) return;
    // Get the minimized block.
    let minimizedBlock = container.getElementsByClassName('minimized')[0];
    if (!minimizedBlock) return;

    let anotherLink = document.querySelector('.process-result-switcher a[href="#' + maximizedBlock.id + '"]');

    if (minimizedBlock.id === href) {
      // Remove the minimized class from target block.
      minimizedBlock.classList.remove('minimized');
      // Add the maximized class to target block.
      minimizedBlock.classList.add('maximized');
      // Remove the maximized class from another block.
      maximizedBlock.classList.remove('maximized');
      // Add the minimized class to another block.
      maximizedBlock.classList.add('minimized');

      // Disable the link.
      target.classList.add('disabled');
      // Switch the images.
      let minimizedImage = target.getElementsByClassName('minimized-image')[0];
      if (minimizedImage) {
        minimizedImage.classList.add('hidden');
      }
      let maximizedImage = target.getElementsByClassName('maximized-image')[0];
      if (maximizedImage) {
        maximizedImage.classList.remove('hidden');
      }
      if (anotherLink && anotherLink.classList.contains('disabled')) {
        // Enable the link.
        anotherLink.classList.remove('disabled');
        // Switch the images.
        let anotherMinimizedImage = anotherLink.getElementsByClassName('minimized-image')[0];
        if (anotherMinimizedImage) {
          anotherMinimizedImage.classList.remove('hidden');
        }
        let anotherMaximizedImage = anotherLink.getElementsByClassName('maximized-image')[0];
        if (anotherMaximizedImage) {
          anotherMaximizedImage.classList.add('hidden');
        }
      }
    }
  }

  // Funntion of the process image click.
  processImageClick = function(event) {
    event.preventDefault();
    event.stopPropagation();

    let image = event.target;
    if (image.tagName !== 'IMG') {
      image = image.closest('img');
    }
    if (!image) return;

    // Get the clicked position.
    let rect = image.getBoundingClientRect();
    let x = event.clientX - rect.left;
    let y = event.clientY - rect.top;
    let width = rect.width;
    let height = rect.height;

    // Calculate the percentage of the clicked position.
    let xPercent = Math.round((x / width) * 1000) / 10;
    let yPercent = Math.round((y / height) * 1000) / 10;
    console.log('Clicked position: ' + xPercent + '% ' + yPercent + '%');

    let position = drupalSettings.process_image_position;
    if (position) {
      let steps = Object.keys(position);
      for (let i = 0; i < steps.length; i++) {
        let step = steps[i];
        let item = position[step];
        if (item.x_left <= xPercent && item.x_right >= xPercent && item.y_top <= yPercent && item.y_bottom >= yPercent) {
          let headerTab = document.querySelector('.results-block-header-item.step-' + step.replace('_', '-'));
          if (headerTab) {
            let link = headerTab.querySelector('a');
            if (link && link.classList.contains('disabled')) {
              // If the link is disabled, return.
              return;
            }
          }
          // Minimize the process block if it is maximized.
          let processBlock = document.getElementsByClassName('process-block')[0];
          if (processBlock && processBlock.classList.contains('maximized')) {
            processBlock.classList.remove('maximized');
            processBlock.classList.add('minimized');

            // Switch the process result switcher.
            let switchResult = document.getElementsByClassName('process-result-switcher-item-link')[0];
            if (switchResult && !switchResult.classList.contains('disabled')) {
              // Add the disabled class.
              switchResult.classList.add('disabled');
              // Switch the images.
              let minimizedImage = switchResult.getElementsByClassName('minimized-image')[0];
              if (minimizedImage) {
                minimizedImage.classList.add('hidden');
              }
              let maximizedImage = switchResult.getElementsByClassName('maximized-image')[0];
              if (maximizedImage) {
                maximizedImage.classList.remove('hidden');
              }
              // Switch the process result switcher.
              let switchProcess = document.getElementsByClassName('process-result-switcher-item-link')[1];
              if (switchProcess && switchProcess.classList.contains('disabled')) {
                // Add the disabled class.
                switchProcess.classList.remove('disabled');
                // Switch the images.
                let minimizedImage = switchProcess.getElementsByClassName('minimized-image')[0];
                if (minimizedImage) {
                  minimizedImage.classList.remove('hidden');
                }
                let maximizedImage = switchProcess.getElementsByClassName('maximized-image')[0];
                if (maximizedImage) {
                  maximizedImage.classList.add('hidden');
                }
              }
            }

          }
          // Maximize the results block.
          let resultsBlock = document.getElementsByClassName('results-block')[0];
          if (resultsBlock && resultsBlock.classList.contains('minimized')) {
            resultsBlock.classList.remove('minimized');
            resultsBlock.classList.add('maximized');
          }
          // Drisplay the step.
          switchResultBlock(step)
        }
      }
    }
  }

  // Function to switch the result block.
  switchResultBlock = function(step) {
    // Deactivate the previous header tab.
    let previousHeaderTab = document.querySelector('.results-block-header-item.active');
    if (previousHeaderTab) {
      previousHeaderTab.classList.remove('active');
    }

    // Deactivate the previous result body.
    let previousResultBody = document.querySelector('.results-block-body-item.active');
    if (previousResultBody) {
      previousResultBody.classList.remove('active');
    }

    // Activate the selected header tab.
    let headerTab = document.querySelector('.results-block-header-item.step-' + step.replace('_', '-'));
    if (headerTab) {
      headerTab.classList.add('active');
    }

    // Activate the selected result body.
    let resultBodyItem = document.getElementById('step-body-' + step.replace('_', '-'));
    if (resultBodyItem) {
      resultBodyItem.classList.add('active');
    }
  }

})(Drupal, drupalSettings);
