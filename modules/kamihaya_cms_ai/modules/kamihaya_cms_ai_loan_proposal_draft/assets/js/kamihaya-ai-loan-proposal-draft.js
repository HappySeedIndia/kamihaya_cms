(function (Drupal, drupalSettings) {

  Drupal.behaviors.draftLoanProposal = {
    attach: function (context, settings) {
      // Add event listeners to the start button.
      let startButton = document.getElementById('draft-start');
      if (startButton) {
        startButton.addEventListener('click', draftLoanProposalStart);
      }
      // Add event listeners to the check button.
      let checkButton = document.getElementById('prompt-check');
      if (checkButton) {
        checkButton.addEventListener('click', checkPrompt);
      }

      // Add event listeners to the close button.
      let promptClose = document.getElementsByClassName('prompt-close')[0];
      if (promptClose) {
        promptClose.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();

          // Enable the check button.
          if (checkButton && checkButton.getAttribute('disabled') == 'disabled') {
            checkButton.removeAttribute('disabled');
          }
        });
      }

      // Add event listeners to the company select.
      let companySelect = document.getElementsByName('company')[0];
      if (companySelect) {
        companySelect.addEventListener('change', function (event) {
          if (companySelect.value === '') {
            return;
          }
          setTimeout(function () {
            // Scroll donw the chat block.
            let chatBlockScroll = document.getElementsByClassName('chat-block-body-scroll')[0];
            if (chatBlockScroll) {
              chatBlockScroll.scrollTo({
                top: chatBlockScroll.scrollHeight,
                behavior: 'smooth'
              });
            }
          }, 100);
        });
      }

      initKamihayaAi();
    }
  };

  let company = undefined;
  let fid = undefined;

  // Start function.
  function draftLoanProposalStart(event) {
    // Prevent default form submission.
    event.preventDefault();
    event.stopPropagation();
    // Get the file id.
    fid = document.getElementsByName('file_upload[fids]')[0].value;
    if (!fid) {
      let fileField = document.getElementsByName('files[file_upload]')[0];
      if (fileField) {
        fileField.classList.add('error');
      }

      // Enable the check button.
      let checkButton = document.getElementById('prompt-check');
      if (checkButton && checkButton.getAttribute('disabled') === 'disabled') {
        checkButton.removeAttribute('disabled');
      }
      return;
    }

    if (!company) {
      // Get the company name.
      company = document.getElementsByName('company')[0].value;
    }
    if (!company) return;

    // Remove the form.
    let form = document.getElementById('company-select-form');
    if (form) {
      form.remove();
    }

    let data = { fid: fid, company: company };
    let chatMessage = [Drupal.t('Starting summarization of the text from PDF.')];

    // Execute step function.
    executeStep('summarize', data, 'pdf_summary', chatMessage, draftLoanProposal, {'pdf_summary_used_prompt': 'Prompt for PDF summarization'}, true);
  }

  // Draft the loan proposal.
  function draftLoanProposal(responseData) {
    // Create the current process object.
    let step = 'summarize';
    currentProcess = { data: { 'summarize': { 'prompts': { 'Prompt for PDF summarization': responseData.prompt.pdf_summary_used_prompt }, 'result': responseData.result } }, timestamp: Date.now() };

    let chatMessage = [Drupal.t('Drafting the loan proposal.')];

    // Execute step function.
    executeStep('draft', {}, 'loan_summary', chatMessage, completeLoanProposal, { 'loan_document_used_prompt': 'Prompt for loan proposal', 'used_company_detail': 'Company detail' }, true);
  }

  // Function of finish loan proposal.
  function completeLoanProposal(responseData) {
    // Add the result to the current process object.
    currentProcess.data['draft'] = { 'prompts': { 'Prompt for loan proposal': responseData.prompt.loan_document_used_prompt, 'Company detail': responseData.prompt.used_company_detail }, 'result': responseData.result };
    currentProcess.timestamp = Date.now();
    // Add the result to the history.
    addHistory(currentProcess);
    // Clear the current process object.
    currentProcess = { data: {}, timestamp: 0 };

    // Add the message to chat.
    addChatMessages([Drupal.t('Do you want to revise the prompts and re-draft the loan proposal?')], 'proceed', 0);

    setTimeout(function () {
    // Add buttons to the last message.
    let chatBlock = document.getElementsByClassName('chat-block-body-content')[0];
    if (chatBlock) {
      // Get the last message.
      let messageList = chatBlock.getElementsByClassName('chat-block-body-item--proceed');
      if (messageList.length > 0) {
        let lastMessage = messageList[messageList.length - 1];
        if (lastMessage) {
          // Append the buttons to the last message.
          let buttons = document.createElement('div');
          buttons.className = 'form-actions js-form-wrapper form-wrapper btn-group--re-draft-loan-proposal';
          let proceedButton = document.createElement('button');
          proceedButton.id = 'revise-prompt';
          proceedButton.className = 'btn btn-primary btn-revise-prompt';
          proceedButton.innerHTML = Drupal.t('Revise prompt');
          proceedButton.addEventListener('click', checkPrompt);
          buttons.appendChild(proceedButton);
          // Add the button to stop.
          let cancelButton = document.createElement('button');
          cancelButton.className = 'btn btn-secondary btn-finish';
          cancelButton.innerHTML = Drupal.t('Not revise prompt');
          cancelButton.addEventListener('click', finishDraftLoanProposal);
          buttons.appendChild(cancelButton);
          lastMessage.appendChild(buttons);
          // Scroll donw the chat block.
          let chatBlockScroll = document.getElementsByClassName('chat-block-body-scroll')[0];
          if (chatBlockScroll) {
            chatBlockScroll.scrollTo({
              top: chatBlockScroll.scrollHeight,
              behavior: 'smooth'
            });
          }
        }
      }
    }
    }, 50);
  }

  // Finish all process of loan proposal.
  function finishDraftLoanProposal() {
    // Remove the prompt revise buttons.
    let buttons = document.getElementsByClassName('btn-group--re-draft-loan-proposal')[0];
    if (buttons) {
      buttons.remove();
    }
    // Add the message to chat.
    addChatMessages([Drupal.t('Finish drafting the loan proposal.')]);
  }

  function draftLoanProposallAgain() {
    // Add message to chat.
    addChatMessages([Drupal.t('Do you want to draft the loan proposal with editting the prompt?')], 'proceed', 0);

    // Add buttons to chat.
    let chatBlock = document.getElementsByClassName('chat-block-body-content')[0];
    if (chatBlock) {
      // Add new messages.
      let child = document.createElement('div');
      child.className = 'chat-block-body-item chat-block-body-item--proceed';
      child.setAttribute('id', 'draft-loan-proposal-again');

      // Add the button to recreate the loan proposal.
      let proceedButton = document.createElement('button');
      proceedButton.className = 'btn btn-primary btn-edit-prompt';
      proceedButton.innerHTML = Drupal.t('Edit prompt & Redraft');
      proceedButton.addEventListener('click', function(event) {
        // Remove the buttons.
        let buttons = document.getElementById('draft-loan-proposal-again');
        if (buttons) {
          buttons.remove();
        }
        checkPrompt(event)
      });
      child.appendChild(proceedButton);
      // Add the button to stop.
      let cancelButton = document.createElement('button');
      cancelButton.className = 'btn btn-secondary btn-edit-prompt-cancel';
      cancelButton.innerHTML = Drupal.t('Not edit prompt & Regenerate');
      cancelButton.addEventListener('click', function (event) {
        // Remove the buttons.
        let buttons = document.getElementById('draft-loan-proposal-again');
        if (buttons) {
          buttons.remove();
        }
        // Didplay the message and remove the buttons.
        addChatMessages([Drupal.t('Finish drafting the loan proposal')]);
      });
      child.appendChild(cancelButton);
      chatBlock.appendChild(child);
      // Scroll donw the chat block.
      let chatBlockScroll = document.getElementsByClassName('chat-block-body-scroll')[0];
      if (chatBlockScroll) {
        chatBlockScroll.scrollTo({
          top: chatBlockScroll.scrollHeight,
          behavior: 'smooth'
        });
      }
    }
  }

  // Check prompt function.
  function checkPrompt(event) {
    // Prevent default form submission.
    event.preventDefault();
    event.stopPropagation();

    // Hide the history block.
    let historyBlock = document.getElementsByClassName('history-block')[0];
    if (historyBlock && !historyBlock.classList.contains('hidden')) {
      historyBlock.classList.add('hidden');
    }

    let checkButton = document.getElementById('prompt-check');
    if (checkButton && checkButton.getAttribute('disabled') != 'disabled') {
      checkButton.setAttribute('disabled', 'disabled');
    }

    let editBtnLabel = 'Execute with the edited prompt';
    let cancelBtnLabel = 'Execute without the edited prompt';
    if (getHistoryCount() > 0) {
      // Change the button text.
      editBtnLabel = 'Revise prompt & Redraft';
      cancelBtnLabel = 'Not revise';

      // Change the title of the prompt block.
      let promptBlockTitle = document.getElementsByClassName('edit-prompt-header-title')[0];
      if (promptBlockTitle) {
        promptBlockTitle.innerHTML = Drupal.t('Revise prompt');
      }
    }

    let promptBlock = document.getElementsByClassName('edit-prompt')[0];
    let summaryPrompt;
    let loanPrompt;
    let companyDetail;
    if (promptBlock) {
      summaryPrompt = document.getElementById('edit-pdf-summary-prompt');
      loanPrompt = document.getElementById('edit-loan-document-prompt');
      companyDetail = document.getElementById('edit-comcompany-detail-prompt');

      // Check if the prompt block is already existing.
      if (summaryPrompt && loanPrompt && companyDetail) {
        if (getHistoryCount() > 0) {
          // Change the button text.
          let editBtn = document.getElementsByClassName('btn-edit-prompt')[0];
          if (editBtn) {
            editBtn.innerHTML = Drupal.t(editBtnLabel);
          }
          // Change the cancel button text.
          let cancelBtn = document.getElementsByClassName('btn-edit-prompt-cancel')[0];
          if (cancelBtn) {
            // Get the class list of the cancel button.
            let classList = cancelBtn.classList;
            // Recreate the button.
            cancelBtn.remove();
            // Create a new button.
            cancelBtn = document.createElement('button');
            cancelBtn.classList = classList;
            cancelBtn.innerHTML = Drupal.t(cancelBtnLabel);
            cancelBtn.removeEventListener('click', finishDraftLoanProposal);
          }
        }
        promptBlock.classList.remove('hidden');
        return;
      }
    }

    if (!company) {
      // Get the company name.
      company = document.getElementsByName('company')[0].value;
    }
    if (!company) return;

    // Execute step function.
    let data = { step: 'prompt', company: company };

    // Send the ajax request.
    sendAjaxRequest(Drupal.url(drupalSettings.ajax_url), data, function (response) {
      // Display the result.
      if (response.pdf_summary_prompt !== undefined && response.loan_document_prompt !== undefined && response.company_detail !== undefined) {
        // Add prompts to the prompt block.
        addPrompt('pdf-summary', 'Prompt for PDF summarization', response.pdf_summary_prompt, true);
        addPrompt('loan-document', 'Prompt for loan proposal', response.loan_document_prompt);
        addPrompt('company-detail', 'Company detail', response.company_detail);
        if (promptBlock) {
          // Change the button text and add event listeners.
          let editBtn = document.getElementsByClassName('btn-edit-prompt')[0];
          if (editBtn) {
            editBtn.innerHTML = Drupal.t(editBtnLabel);
            editBtn.addEventListener('click', draftLoanProposalWithPrompt);
          }

          // Change the cancel button text and add event listeners.
          let cancelBtn = document.getElementsByClassName('btn-edit-prompt-cancel')[0];
          if (cancelBtn) {
            cancelBtn.innerHTML = Drupal.t(cancelBtnLabel);
            if (getHistoryCount() > 0) {
              // Add event listener to the cancel button.
              cancelBtn.addEventListener('click', finishDraftLoanProposal);
            } else {
              // Add event listener to the cancel button.
              cancelBtn.addEventListener('click', function(event) {
                // Remove the prompt block.
                let promptBlock = document.getElementsByClassName('edit-prompt')[0];
                if (promptBlock) {
                  promptBlock.classList.add('hidden');
                }
                draftLoanProposalStart(event);
              });
            }
          }

          if (promptBlock.classList.contains('hidden')) {
            promptBlock.classList.remove('hidden');
          }
        }
      }
    }, function (error) {
      console.error('Error:', error);
    })
  }

  // Execute draft loan proposal with prompt.
  function draftLoanProposalWithPrompt(event) {
    // Prevent default form submission.
    event.preventDefault();
    event.stopPropagation();

    // Initiialize the header tabs.
    initHeaderTabs();

    // Disable the history links
    disableHistoryLinks();

    // Remove the prompt block.
    let promptBlock = document.getElementsByClassName('edit-prompt')[0];
    if (promptBlock) {
      promptBlock.classList.add('hidden');
    }

    // Remove the prompt revise buttons.
    let buttons = document.getElementsByClassName('btn-group--re-draft-loan-proposal')[0];
    if (buttons) {
      buttons.remove();
    }

    // Get the file id.
    if (!fid) {
      fid = document.getElementsByName('file_upload[fids]')[0].value;
    }
    if (!fid) {
      let fileField = document.getElementsByName('files[file_upload]')[0];
      if (fileField) {
        fileField.classList.add('error');
      }

      // Enable the check button.
      let checkButton = document.getElementById('prompt-check');
      if (checkButton && checkButton.getAttribute('disabled') === 'disabled') {
        checkButton.removeAttribute('disabled');
      }
      return;
    }

    if (!company) {
      // Get the company name.
      company = document.getElementsByName('company')[0].value;
    }
    if (!company) return;

    let summaryPrompt = document.getElementById('edit-pdf-summary-prompt').value;
    let loanPrompt = document.getElementById('edit-loan-document-prompt').value;
    let companyDetail = document.getElementById('edit-company-detail-prompt').value;

    if (!summaryPrompt || !loanPrompt || !companyDetail) return;
    // Send the ajax request.
    let data = { fid: fid, company: company, summary_prompt: summaryPrompt, loan_prompt: loanPrompt, company_detail: companyDetail };

    // Remove the form.
    let form = document.getElementById('company-select-form');
    if (form) {
      form.remove();
    }

    let chatMessage = [Drupal.t('Starting summarization of the document with edited prompts.')];

    // Execute step function.
    executeStep('summarize', data, 'pdf_summary', chatMessage, draftLoanProposal, { 'pdf_summary_used_prompt': 'Prompt for PDF summarization' }, true);
  }

})(Drupal, drupalSettings);
