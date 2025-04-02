(function (Drupal, drupalSettings) {
  let suspend = false;

  Drupal.behaviors.documentCheckStart = {
    attach: function (context, settings) {
      let startButton = document.getElementById('document-check-start');
      if (startButton) {
        startButton.addEventListener('click', documentCheckStart);
      }

      let headerTabs = document.querySelectorAll('.results-block-header-item a');
      if (headerTabs) {
        for (let i = 0; i < headerTabs.length; i++) {
          headerTabs[i].addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            let tab = this.getAttribute('href');
            if (tab) {
              tab = tab.replace('#', '');
            }

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

            // Activate the header tab.
            this.parentElement.classList.add('active');

            // Activate the result body.
            let resultBody = document.getElementById(tab);
            if (resultBody) {
              resultBody.classList.add('active');
            }
          });
        }
      }

      let stopButtons = document.querySelectorAll('.btn-stop');
      if (stopButtons) {
        for (let i = 0; i < stopButtons.length; i++) {
          stopButtons[i].addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            documentCheckSuspend();
          });
        }
      }
    }
  };


  // Ajax request function.
  function sendAjaxRequest(url, data = {}, callback = null, errorCallback = null) {
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

  // Document check start function.
  function documentCheckStart(event) {
    // Prevent default form submission.
    event.preventDefault();
    event.stopPropagation();
    // Get the file id.
    let fid = document.getElementsByName('file_upload[fids]')[0].value;
    if (!fid) return;

    // Remove the form.
    let form = document.getElementById('file-upload-form');
    if (form) {
      form.remove();
    }

    let data = { fid: fid };
    let chatMessage = [Drupal.t('Starting summarization of the document.')];

    // Execute step function.
    executeStep('summarize', data, 'spec_summary', chatMessage, copyrightCheck);
  }

  // Check the copyright.
  function copyrightCheck() {
    let chatMessage = [Drupal.t('Checking the copyright of the document.')];

    // Execute step function.
    executeStep('copyright_check', {}, 'checkresult', chatMessage, companyRuleChack);
  }

  // Check the company rule.
  function companyRuleChack() {
    let chatMessage = [Drupal.t('Checking the company rule of the document.')];

    // Execute step function.
    executeStep('company_check', {}, 'recheckresult', chatMessage);
  }

  // Execute step function.
  function executeStep(step, data, responseKey, chatMessage, nextCallback = null) {
    // Set the current step.
    let stepTag = document.getElementById('current-step');
    if (stepTag) {
      stepTag.value = step;
    }

    // Add message to chat.
    let chatBlock = document.getElementsByClassName('chat-block-body-content')[0];
    for (let i = 0; i < chatMessage.length; i++) {
      let child = document.createElement('div');
      child.className = 'chat-block-body-item chat-block-body-item--info';
      child.innerHTML = chatMessage[i];
      chatBlock.appendChild(child);
    }

    // Display result body.
    let resultBody = document.getElementsByClassName('results-block')[0];
    if (resultBody && resultBody.classList.contains('results-block--hidden')) {
      resultBody.classList.remove('results-block--hidden');
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
    sendAjaxRequest(Drupal.url('ajax-handler-document-check'), data, function (response) {
      ajaxSuccess(response, step, responseKey, loadingAnimation, chatBlock, headerTab);
      if (nextCallback) {
        nextCallback();
      }
    }, function(error) {
      ajaxError(error, step, loadingAnimation, chatBlock, headerTab);
    })
  };

  // Ajax success handling function.
  function ajaxSuccess(response, step, responseKey, loadingAnimation, chatBlock, headerTab) {
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
      let child = document.createElement('div');
      child.className = 'chat-block-body-item chat-block-body-item--info';
      child.innerHTML = response.message;
      chatBlock.appendChild(child);
    }
    // Display the result.
    if (response[responseKey] !== undefined) {
      let result = response[responseKey];
      let resultBlock = document.getElementById('step-body-' + step.replace('_', '-'));
      resultBlock.innerHTML = '<div class="result ' + responseKey.replace('_', '-') + '">' + result + '</div>';
    }
  };

  // Error handling function.
  function ajaxError(error, step, loadingAnimation, chatBlock, headerTab) {
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
      // Add message to chat.
      console.error('Ajax Error:', error);
      let child = document.createElement('div');
      child.className = 'chat-block-body-item chat-block-body-item--error';
      child.innerHTML = error;
      chatBlock.appendChild(child);

      // Display the error to body.
      let resultBlock = document.getElementById('step-body-' + step.replace('_', '-'));
      resultBlock.innerHTML = '<div class="result error">' + error + '</div>';
    }
  }

  // Suspend handling function.
  function documentCheckSuspend() {
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
    let chatBlock = document.getElementsByClassName('chat-block-body-content')[0];
    let child = document.createElement('div');
    child.className = 'chat-block-body-item chat-block-body-item--info';
    child.innerHTML = Drupal.t('The process has been stopped.');
    chatBlock.appendChild(child);

    // Display the error to body.
    let resultBlock = document.getElementById('step-body-' + step.replace('_', '-'));
    resultBlock.innerHTML = '<div class="result suspended">' + Drupal.t("The result isn't created because the process has been suspended."); + '</div>';

    // Set the suspend flag.
    suspend = true;
  }

})(Drupal, drupalSettings);
