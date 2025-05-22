<?php

namespace Drupal\kamihaya_cms_ai_loan_proposal_draft\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\kamihaya_cms_loan_proposal_api\ExabaseClient;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Company select form.
 */
class CompanySelectForm extends FormBase {

  /**
   * The constructor of CompanySelectForm.
   *
   * @param AccountProxyInterface $currentUser
   *   The current user.
   * @param ExabaseClient $exabaseClient
   *  The Exabase client.
   */
  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected ExabaseClient $exabaseClient,
    ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('kamihaya_cms_loan_proposal_api.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'company_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = User::load($this->currentUser->id());
    $name = $user->hasField('field_name') && !empty($user->get('field_name')->value) ? $user->get('field_name')->value : $user->getAccountName();

    $form['welcome'] = [
      '#type' => 'markup',
      '#markup' => "<p class='welcome'>" . $this->t("Hello @name!", ['@name' => $name]) . '</p>',
    ];

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t("Which company do you want to create a Loan Proposal?") . '</p>',
    ];

    try {
      $company_list = $this->exabaseClient->getCompanyList();
      if (empty($company_list)) {
        $form['message'] = [
          '#type' => 'markup',
          '#markup' => '<p>' . $this->t("There are no companies to select.") . '</p>',
        ];
        return $form;
      }
    }
    catch (\Exception $e) {
      $this->logger('kamihaya_cms_ai_loan_proposal_draft')->error($this->t('Error fetching company list: @message', ['@message' => $e->getMessage()]));
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t("There was an error fetching the company list.") . '</p>',
      ];
      return $form;
    }

    $form['company'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a company'),
      '#options' => array_combine(array_values($company_list), $company_list),
      '#required' => TRUE,
    ];

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'file-upload'],
      '#states' => [
        'invisible' => [
          'select[name="company"]' => ['value' => ''],
        ],
      ],
    ];


    $form['container']['file_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Past loan proposal documents'),
      '#upload_location' => 'public://loan_proposal/documents/',
      '#description' => $this->t('Allowed extensions: pdf'),
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf'],
      ],
      '#required' => TRUE,
    ];

    $form['container']['message'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Do you wan to check the prompt or draft a loan proposal?'),
    ];

    $form['container']['actions'] = [
      '#type' => 'actions',
    ];

    $form['container']['actions']['check'] = [
      '#type' => 'submit',
      '#value' => $this->t('Prompt check'),
      '#attributes' => [
        'id' => 'prompt-check',
        'class' => ['btn-secondary'],
      ],
    ];

    $form['container']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Draft loan proposal'),
      '#attributes' => ['id' => 'draft-start'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  function ajaxSelectCompanyCallback(array &$form, FormStateInterface $form_state) {
    return $form['file_upload'];
  }

}
