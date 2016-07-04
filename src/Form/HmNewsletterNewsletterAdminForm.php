<?php

namespace Drupal\hm_newsletter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Class HmNewsletterNewsletterAdminForm.
 *
 * @package Drupal\hm_newsletter\Form
 */
class HmNewsletterNewsletterAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hm_newsletter_newsletter_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hm_newsletter.settings');

    foreach (Element::children($form['hm_newsletter']) as $variable) {
      $config->set($variable, $form_state->getValue($form['hm_newsletter'][$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hm_newsletter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $hm_newsletter_settings = $this->config('hm_newsletter.settings');
    $form = [];
    $form['hm_newsletter'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Harbourmaster newsletter configuration'),
    ];

    $form['hm_newsletter']['hm_environment'] = array(
      '#title' => 'Environment',
      '#description' => $this->t('Can be overwritten by settings.php via $config[\'hm_newsletter.settings\'][\'hm_environment\'] = \'production\'/\'staging\';.'),
      '#type' => 'select',
      '#options' => array(
        'staging' => 'staging',
        'production' => 'production',
      ),
      '#default_value' => $hm_newsletter_settings->get('hm_environment'),
    );

    $form['hm_newsletter']['hm_client_id'] = array(
      '#title' => $this->t('Client id'),
      '#description' => $this->t('Client id will be used for agreements.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $hm_newsletter_settings->get('hm_client_id'),
    );

    $form['hm_newsletter']['hm_imprint_text'] = array(
      '#title' => $this->t('Imprint text'),
      '#description' => $this->t('Text is displayed in the footer of the newsletter subscription form.'),
      '#type' => 'textarea',
      '#default_value' => $hm_newsletter_settings->get('hm_imprint_text'),
    );

    return parent::buildForm($form, $form_state);
  }

}
