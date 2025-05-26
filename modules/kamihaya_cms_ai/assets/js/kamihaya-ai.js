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

    // Add event listeners to the prompt close button.
    let promptClose = document.getElementsByClassName('prompt-close')[0];
    if (promptClose) {
      promptClose.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        // Close the prompt block.
        let promptBlock = document.getElementsByClassName('edit-prompt')[0];
        if (promptBlock) {
          promptBlock.classList.add('hidden');
        }
      });
    }

    // Add event listeners to the history close button.
    let historyClose = document.getElementsByClassName('history-close')[0];
    if (historyClose) {
      historyClose.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        // Close the history block.
        let historyBlock = document.getElementsByClassName('history-block')[0];
        if (historyBlock) {
          historyBlock.classList.add('hidden');
        }
      });
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

        let mainContainer = document.getElementsByClassName('kamihaya-ai-container-main')[0];
        if (mainContainer) {
          if (result.api_error_mode == true) {
            // Add the error mode class to the main container.
            if (!mainContainer.classList.contains('api-error-mode')) {
              mainContainer.classList.add('api-error-mode');
            }
          }
          else {
            // Remove the error mode class to the main container.
            mainContainer.classList.remove('api-error-mode');
          }
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
  executeStep = function (step, data, responseKey, chatMessage, nextCallback = null, prompts = null, rerunnable = false) {
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
          links[i].addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            let target = event.target;
            if (target.tagName !== 'A') {
              target = target.closest('a');
            }
            let href = target.getAttribute('href');
            // Remove the hash from the href.
            href = href.replace('#', '');
            if (href === '') return;
            // Switch the blocks.
            switchBlocks(href);
          });
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

    // Remove the inner HTML of the result block.
    let result = document.querySelector('#step-body-' + step.replace('_', '-') + ' .result');
    if (result) {
      result.remove();
    }

    // Remove the prompt block.
    let promptBlock = document.querySelector('#step-body-' + step.replace('_', '-') + ' .prompt-container');
    if (promptBlock) {
      promptBlock.remove();
    }

    let animationTag = document.querySelector('#step-body-' + step.replace('_', '-') + ' .results-block-body-item-movie');
    if (animationTag && animationTag.classList.contains('hidden')) {
      // Show the animation tag.
      animationTag.classList.remove('hidden');
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
      let returnValue = ajaxSuccess(response, step, responseKey, loadingAnimation, headerTab, prompts, rerunnable);
      if (nextCallback) {
        nextCallback(returnValue);
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
  ajaxSuccess = function (response, step, responseKey, loadingAnimation, headerTab, prompts = null, rerunnable = false) {
    // Stop the loading animation.
    if (loadingAnimation) {
      loadingAnimation.pause();
    }

    // Change the class of header tab.
    if (headerTab) {
      headerTab.classList.remove('running');
      headerTab.classList.add('finished');
    }

    let animationTag = document.querySelector('#step-body-' + step.replace('_', '-') + ' .results-block-body-item-movie');
    if (animationTag) {
      if (rerunnable) {
        // If need to re-run, Hide the animation tag.
        animationTag.classList.add('hidden');
      } else {
       // Remove the animation tag.
        animationTag.remove();
        animationTag = null;
      }
    }

    if (response.message) {
      // Add message to chat.
      addChatMessages([response.message], 'info', 0);
    }
    // Display the result.
    if (response[responseKey] !== undefined) {
      let result = response[responseKey];
      let displayPrommts = {};

     // let resultBlock = document.getElementById('step-body-' + step.replace('_', '-'));
      // Return value of the function.
      let returnValue = { result: result, prompt: null };

      //let promptHTML = ''
      // Display the prompt.
      if (prompts && Object.keys(prompts).length > 0) {
        let keys = Object.keys(prompts);
        //promptHTML += '<div class="prompt-container">';
        returnValue.prompt = {};
        for (let i = 0; i < keys.length; i++) {
          let promptKey = keys[i];
          let promptLabel = prompts[promptKey] ? prompts[promptKey] : 'Prompt';
          let prompt = response[promptKey];
          displayPrommts[promptLabel] = prompt;
          if (prompt && prompt.length > 0) {
            returnValue.prompt[promptKey] = prompt;
          }
        }
      }

      createResultsBlock(step, result, displayPrommts, rerunnable);
      return returnValue;
    }

  };

  // Error handling function.
  ajaxError = function (error, step, loadingAnimation, headerTab, rerunnable = false) {
    // Stop the loading animation.
    if (loadingAnimation) {
      loadingAnimation.pause();
    }

    // Change the class of header tab.
    if (headerTab) {
      headerTab.classList.remove('running');
      headerTab.classList.add('failed');
    }

    let animationTag = document.querySelector('#step-body-' + step.replace('_', '-') + ' .results-block-body-item-movie');
    if (animationTag) {
      if (rerunnable) {
        // If need to re-run, Hide the animation tag.
        animationTag.classList.add('hidden');
      } else {
        // Remove the animation tag.
        animationTag.remove();
        animationTag = null;
      }
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

  // Create a result block function.
  createResultsBlock = function (step, result, prompts = null, keepAnimation = false) {
    let resultBlock = document.getElementById('step-body-' + step.replace('_', '-'));
    let promptHTML = ''

    // Display the prompt.
    if (prompts && Object.keys(prompts).length > 0) {
      let labels = Object.keys(prompts);
      promptHTML += '<div class="prompt-container">';
      for (let i = 0; i < labels.length; i++) {
        let label = labels[i];
        let promptLabel = typeof label === 'number' ? 'Prompt' : label;
        let prompt = prompts[label];
        if (prompt && prompt.length > 0) {
          // Add prompt to the prompt list.
          promptHTML += '<div class="prompt-item"><div class="prompt-title">' + Drupal.t(promptLabel) + '</div>';
          promptHTML += '<div class="prompt ' + step.replace('_', '-') + '">' + prompt + '</div></div>';
        }
      }
      promptHTML += '</div>';
    }
    let animationTag = undefined;
    if (keepAnimation) {
      animationTag = document.querySelector('#step-body-' + step.replace('_', '-') + ' .results-block-body-item-movie');
    }
    // Add the result to the result block.
    resultBlock.innerHTML = '<div class="result ' + step.replace('_', '-') + '">' + result + '</div>' + promptHTML;
    if (keepAnimation && animationTag) {
      // Add the animation tag.
      resultBlock.appendChild(animationTag);
    }
  }

  // Initialize the header tabs.
  initHeaderTabs = function() {
    let headerTabs = document.querySelectorAll('.results-block-header-item');
    if (headerTabs) {
      for (let i = 0; i < headerTabs.length; i++) {
        // Remobe all classes from the header tab.
        headerTabs[i].classList.remove('active', 'running', 'finished', 'failed', 'suspended');
        // Add the disabled class to the header link.
        let link = headerTabs[i].querySelector('a');
        if (link && !link.classList.contains('disabled')) {
          link.classList.add('disabled');
        }
      }
    }
  }

  // Switch blocks function.
  switchBlocks = function(target) {
    // Get the container.
    let container = document.getElementsByClassName('kamihaya-ai-container-main')[0];
    if (!container) return;

    // Get the maximized block.
    let maximizedBlock = container.getElementsByClassName('maximized')[0];
    if (!maximizedBlock) return;
    // Get the minimized block.
    let minimizedBlock = container.getElementsByClassName('minimized')[0];
    if (!minimizedBlock) return;

    if (minimizedBlock.id === target) {
      // Remove the minimized class from target block.
      minimizedBlock.classList.remove('minimized');
      // Add the maximized class to target block.
      minimizedBlock.classList.add('maximized');
      // Remove the maximized class from another block.
      maximizedBlock.classList.remove('maximized');
      // Add the minimized class to another block.
      maximizedBlock.classList.add('minimized');

      let switcherLinks = document.getElementsByClassName('process-result-switcher-item-link');
      if (switcherLinks && switcherLinks.length > 0) {
        for (let i = 0; i < switcherLinks.length; i++) {
          let switcherLink = switcherLinks[i];
          let href = switcherLink.getAttribute('href');
          if (href == '#' + target) {
            // Disable the link.
            switcherLink.classList.add('disabled');
            // Switch the images.
            let minimizedImage = switcherLink.getElementsByClassName('minimized-image')[0];
            if (minimizedImage) {
              minimizedImage.classList.add('hidden');
            }
            let maximizedImage = switcherLink.getElementsByClassName('maximized-image')[0];
            if (maximizedImage) {
              maximizedImage.classList.remove('hidden');
            }
          }
          else {
            // Enable the link.
            switcherLink.classList.remove('disabled');
            // Switch the images.
            let minimizedImage = switcherLink.getElementsByClassName('minimized-image')[0];
            if (minimizedImage) {
              minimizedImage.classList.remove('hidden');
            }
            let maximizedImage = switcherLink.getElementsByClassName('maximized-image')[0];
            if (maximizedImage) {
              maximizedImage.classList.add('hidden');
            }
          }
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

  // Add prompt tp the prompt list.
  addPrompt = function(name, label, prompt, active) {
    let promptBlock = document.getElementsByClassName('edit-prompt')[0];
    if (promptBlock) {
      name = name.replace('_', '-');
      let promptHeaderList = promptBlock.getElementsByClassName('edit-prompt-buttons-list')[0];
      let promptContent = promptBlock.getElementsByClassName('edit-prompt-body-content')[0];
      if (promptContent && promptHeaderList) {
        promptHeaderList.classList.remove('hidden');
        let promptItem = document.getElementById('edit-' + name + '-prompt');
        let = headerButton = undefined;
        let container = undefined;
        if (!promptItem) {
          // Create a header button list item.
          let headerButtonListItem = document.createElement('li');
          headerButtonListItem.classList.add('edit-prompt-buttons-item', 'prompt-' + name);
          promptHeaderList.appendChild(headerButtonListItem);

          // Create a header button.
          headerButton = document.createElement('a');
          headerButton.classList.add('edit-prompt-buttons-item-link');
          if (active) {
            headerButton.classList.add('active');
          }
          headerButton.setAttribute('href', '#' + name + '-prompt');
          headerButton.innerHTML = label;
          headerButton.addEventListener('click', switchPrompt);
          headerButtonListItem.appendChild(headerButton);

          // Create a prompt container.
          container = document.createElement('div');
          container.classList.add('prompt-container');
          if (active) {
            container.classList.add('active');
          }
          container.id = name + '-prompt';
          promptContent.appendChild(container);

          // Create a new textarea for the PDF summary prompt.
          promptItem = document.createElement('textarea');
          promptItem.id = 'edit-' + name + '-prompt';
          container.appendChild(promptItem);
          promptItem.value = prompt;
        }
        else {
          // Get the header button and container.
          headerButton = promptHeaderList.querySelector('.prompt-' + name + ' a');
          container = document.getElementById(name + '-prompt');
        }
        if (active) {
          // Add the active class to the header button and container.
          if (!headerButton.classList.contains('active')) {
            headerButton.classList.add('active');
          }
          if (!container.classList.contains('active')) {
            container.classList.add('active');
          }
        }
        else {
          // Remove the active class from the header button and container.
          if (headerButton.classList.contains('active')) {
            headerButton.classList.remove('active');
          }
          if (container.classList.contains('active')) {
            container.classList.remove('active');
          }
        }
      }
    }
  }

  // Function to switch the prompt.
  switchPrompt = function(event) {
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

    // Hide the current prompt.
    let currentPrompt = document.querySelector('.prompt-container.active');
    if (currentPrompt) {
      currentPrompt.classList.remove('active');
    }

    // Show the selected prompt.
    let newPrompt = document.getElementById(href);
    if (newPrompt) {
      newPrompt.classList.add('active');
    }
    // Deactivate the previous header button.
    let previiousHeaderButton = document.querySelector('.edit-prompt-buttons-item-link.active');
    if (previiousHeaderButton) {
      previiousHeaderButton.classList.remove('active');
    }
    // Activate the selected header button.
    target.classList.add('active');
  }

  // History handling function.
  let history = [];
  let currentProcess = { data: {}, timestamp: 0 };

  // Function to add a new history.
  addHistory = function(result) {
    // Add the result to the history.
    history.push(result);

    if (history.length > 1) {
      // Display and refresh the history links.
      displayHistoryLinks(history.length);
    }
  }

  // Function to get the history count.
  getHistoryCount = function() {
    let historyCount = 0;
    if (history && history.length > 0) {
      historyCount = history.length;
    }
    return historyCount;
  }

  // Disable the history links.
  disableHistoryLinks = function() {
    let historyLinks = document.getElementsByClassName('results-block-header-history')[0];
    if (historyLinks) {
      let historyLinkItems = historyLinks.getElementsByTagName('li');
      if (historyLinkItems && historyLinkItems.length > 0) {
        for (let i = 0; i < historyLinkItems.length; i++) {
          let historyLink = historyLinkItems[i].getElementsByTagName('a')[0];
          if (historyLink && !historyLink.classList.contains('disabled')) {
            historyLink.classList.add('disabled');
          }
        }
      }
    }
  }

  // Function to display the history links.
  displayHistoryLinks = function (totalItems) {
    let historyLinks = document.getElementsByClassName('results-block-header-history')[0];
    if (historyLinks) {
      if (historyLinks.classList.contains('hidden')) {
        historyLinks.classList.remove('hidden');
      }
      // Clear the history links.
      let historyLinkItems = historyLinks.getElementsByTagName('li');
      if (historyLinkItems && historyLinkItems.length > 0) {
        for (let i = 0; i < historyLinkItems.length; i++) {
          historyLinkItems[i].remove();
          i--;
        }
      }
      // Add the history links.
      const maxDisplay = 3;
      const displayCount = Math.min(maxDisplay, totalItems);
      const start = totalItems - displayCount;

      // Display links.
      for (let i = totalItems - 1, label = 1; i >= start; i--, label++) {
        let historyItem = document.createElement('li');
        historyItem.classList.add('results-block-header-history-item');
        let historyLink = document.createElement('a');
        historyLink.classList.add('results-block-header-history-link');
        if (label === 1) {
          // Add the active class to the first history link.
          historyLink.classList.add('active');
        }
        historyLink.setAttribute('href', '#' + i);
        historyLink.textContent = label;
        historyLink.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          let index = this.getAttribute('href');
          if (index) {
            index = index.replace('#', '');
          }
          // Remove the active class from the previous history link.
          let previousHistoryLink = document.querySelector('.results-block-header-history-link.active');
          if (previousHistoryLink) {
            previousHistoryLink.classList.remove('active');
          }
          // Add the active class to the selected history link.
          this.classList.add('active');
          // Switch the result block.
          displayResultHistory(index);
        });
        historyItem.appendChild(historyLink);
        historyLinks.appendChild(historyItem);
      }

      // Add a link to view all items.
      if (totalItems > maxDisplay) {
        let historyItem = document.createElement('li');
        historyItem.classList.add('results-block-header-history-item', 'all');
        let historyLink = document.createElement('a');
        historyLink.classList.add('results-block-header-history-link');
        historyLink.setAttribute('href', '#all');
        historyLink.textContent = Drupal.t('All');
        historyLink.addEventListener('click', displayHisrotyBlock);
        historyItem.appendChild(historyLink);
        historyLinks.appendChild(historyItem);
      }
    }
  }

  // Function to display the selected result history.
  displayResultHistory = function (index) {
    // Hide the history block.
    let historyBlock = document.getElementsByClassName('history-block')[0];
    if (historyBlock && !historyBlock.classList.contains('hidden')) {
      historyBlock.classList.add('hidden');
    }

    let result = history[index];
    let steps = Object.keys(result.data);
    for (let i = 0; i < steps.length; i++) {
      let step = steps[i];
      let process = result.data[step];
      createResultsBlock(step, process.result, process.prompts, true);
      let resultBlock = document.getElementById('step-body-' + step.replace('_', '-'));
      let headerTab = document.querySelector('.results-block-header-item.step-' + step.replace('_', '-'));
      if (!resultBlock || !headerTab) {
        continue;
      }
      if (i === 0 && !headerTab.classList.contains('active')) {
        // Add the active class to the header tab.
        headerTab.classList.add('active');
        // Add the active class to the result block.
        resultBlock.classList.add('active');
      }
      if (i > 0 && headerTab.classList.contains('active')) {
        // Remove the active class from the header tab.
        headerTab.classList.remove('active');
        // Remove the active class from the result block.
        resultBlock.classList.remove('active');
      }
    }

    // Maximize the results block if it is minimized.
    let resultsBlock = document.getElementsByClassName('results-block')[0];
    if (resultsBlock && resultsBlock.classList.contains('minimized')) {
      switchBlocks('results-block');
    }
  }

  // Function to display the history block.
  displayHisrotyBlock = function (event) {
    event.preventDefault();
    event.stopPropagation();

    // Add hitory to the history block.
    let historyBlock = document.getElementsByClassName('history-block')[0];
    if (historyBlock) {
      let historyBlockBody = historyBlock.getElementsByClassName('history-block-body')[0];
      if (historyBlockBody) {
        // Clear the history block.
        let historyBlockItems = historyBlockBody.getElementsByTagName('div');
        if (historyBlockItems && historyBlockItems.length > 0) {
          for (let i = 0; i < historyBlockItems.length; i++) {
            historyBlockItems[i].remove();
            i--;
          }
        }
        for (let i = history.length - 1; i > -1; i--) {
          let result = history[i];
          let time = new Date(result.timestamp);
          let timeString = time.toLocaleString(drupalSettings.date_format, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
          });
          let historyItem = document.createElement('div');
          historyItem.classList.add('history-block-body-item');
          let historyLink = document.createElement('a');
          historyLink.classList.add('history-block-body-item-link');
          historyLink.setAttribute('href', '#' + i);
          historyLink.textContent = timeString;
          historyLink.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            let index = this.getAttribute('href');
            if (index) {
              index = index.replace('#', '');
            }
            // Switch the result block.
            displayResultHistory(index);
          });
          historyItem.appendChild(historyLink);
          historyBlockBody.appendChild(historyItem);
        }

        // Remove the hidden class from the history block.
        historyBlock.classList.remove('hidden');
      }
    }

  }

})(Drupal, drupalSettings);
