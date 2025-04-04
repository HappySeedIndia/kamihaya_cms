(function (Drupal, drupalSettings) {

  Drupal.behaviors.documentCheckStart = {
    attach: function (context, settings) {
      let startButton = document.getElementById('document-check-start');
      if (startButton) {
        startButton.addEventListener('click', documentCheckStart);
      }
      initKamihayaAi();
    }
  };

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

})(Drupal, drupalSettings);
