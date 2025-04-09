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
          // Close the prompt block.
          let promptBlock = document.getElementsByClassName('edit-prompt')[0];
          if (promptBlock) {
            promptBlock.classList.add('hidden');
          }
        });
      }
      initKamihayaAi();
    }
  };

  // Start function.
  function draftLoanProposalStart(event) {
    // Prevent default form submission.
    event.preventDefault();
    event.stopPropagation();
    // Get the file id.
    let fid = document.getElementsByName('file_upload[fids]')[0].value;
    if (!fid) return;

    let company = document.getElementsByName('company')[0].value;
    if (!company) return;

    // Remove the form.
    let form = document.getElementById('company-select-form');
    if (form) {
      form.remove();
    }

    let data = { fid: fid, company: company };
    let chatMessage = [Drupal.t('Starting summarization of the text from PDF.')];

    // Execute step function.
    executeStep('summarize', data, 'pdf_summary', chatMessage, draftLoanProposal, 'pdf_summary_used_prompt');
  }

  // Draft the loan proposal.
  function draftLoanProposal() {
    let chatMessage = [Drupal.t('Drafting the loan proposal.')];

    // Execute step function.
    executeStep('draft', {}, 'loan_summary', chatMessage, null, 'loan_document_used_prompt');
  }

  // Check prompt function.
  function checkPrompt(event) {
    // Prevent default form submission.
    event.preventDefault();
    event.stopPropagation();

    let checkButton = document.getElementById('prompt-check');
    if (checkButton && checkButton.getAttribute('disabled') != 'disabled') {
      checkButton.setAttribute('disabled', 'disabled');
    }

    let promptBlock = document.getElementsByClassName('edit-prompt')[0];
    let summaryPrompt;
    let loanPrompt;
    if (promptBlock) {
      summaryPrompt = document.getElementById('edit-summary-prompt');
      loanPrompt = document.getElementById('edit-loan-prompt');

      // Check if the prompt block is already existing.
      if (summaryPrompt && loanPrompt) {
        promptBlock.classList.remove('hidden');
        return;
      }
    }

    // Execute step function.
    let data = { step: 'prompt' };

    // Send the ajax request.
    sendAjaxRequest(Drupal.url(drupalSettings.ajax_url), data, function (response) {
      // Display the result.
      if (response.pdf_summary_prompt !== undefined && response.loan_document_prompt !== undefined) {
        let pdf_summary_prompt = response.pdf_summary_prompt;
        let loan_document_prompt = response.loan_document_prompt;
        if (promptBlock) {
          let promptCntent = promptBlock.getElementsByClassName('edit-prompt-body-content')[0];
          if (promptCntent) {
            if (!summaryPrompt) {
              // Create a prompt container.
              let container = document.createElement('div');
              container.classList.add('prompt-container');
              promptCntent.appendChild(container);
              // Create a prompt name.
              let promptName = document.createElement('div');
              promptName.classList.add('prompt-name');
              promptName.innerHTML = Drupal.t('Prompt for PDF summarization');
              container.appendChild(promptName);

              // Create a new textarea for the PDF summary prompt.
              summaryPrompt = document.createElement('textarea');
              summaryPrompt.id = 'edit-summary-prompt';
              container.appendChild(summaryPrompt);
            }
            summaryPrompt.value = pdf_summary_prompt;

            if (!loanPrompt) {
              // Create a prompt container.
              let container = document.createElement('div');
              container.classList.add('prompt-container');
              promptCntent.appendChild(container);

              // Create a prompt name.
              let promptName = document.createElement('div');
              promptName.classList.add('prompt-name');
              promptName.innerHTML = Drupal.t('Prompt for loan proposal');
              container.appendChild(promptName);

              // Create a new textarea for the loan document prompt.
              loanPrompt = document.createElement('textarea');
              loanPrompt.id = 'edit-loan-prompt';
              container.appendChild(loanPrompt);
            }
            loanPrompt.value = loan_document_prompt;
          }

          // Change the button text and add event listeners.
          let etidBtn = document.getElementsByClassName('btn-edit-prompt')[0];
          if (etidBtn) {
            etidBtn.innerHTML = Drupal.t('Execute with the edited prompt');
            etidBtn.addEventListener('click', draftLoanProposalWithPrompt);
          }

          // Change the cancel button text and add event listeners.
          let cancelBtn = document.getElementsByClassName('btn-edit-prompt-cancel')[0];
          if (cancelBtn) {
            cancelBtn.innerHTML = Drupal.t('Execute without the edited prompt');
            cancelBtn.addEventListener('click', function(event) {
              // Remove the prompt block.
              let promptBlock = document.getElementsByClassName('edit-prompt')[0];
              if (promptBlock) {
                promptBlock.classList.add('hidden');
              }
              draftLoanProposalStart(event);
            });
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

    // Remove the prompt block.
    let promptBlock = document.getElementsByClassName('edit-prompt')[0];
    if (promptBlock) {
      promptBlock.classList.add('hidden');
    }

    // Get the file id.
    let fid = document.getElementsByName('file_upload[fids]')[0].value;
    if (!fid) return;

    let company = document.getElementsByName('company')[0].value;
    if (!company) return;

    let summaryPrompt = document.getElementById('edit-summary-prompt').value;
    let loanPrompt = document.getElementById('edit-loan-prompt').value;
    if (!summaryPrompt || !loanPrompt) return;
    // Send the ajax request.
    let data = { fid: fid, company: company, summary_prompt: summaryPrompt, loan_prompt: loanPrompt };

    // Remove the form.
    let form = document.getElementById('company-select-form');
    if (form) {
      form.remove();
    }

    let chatMessage = [Drupal.t('Starting summarization of the document with edited prompts.')];

    // Execute step function.
    executeStep('summarize', data, 'pdf_summary', chatMessage, draftLoanProposal, 'pdf_summary_used_prompt');
  }

})(Drupal, drupalSettings);
