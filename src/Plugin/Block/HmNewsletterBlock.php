<?php

namespace Drupal\hm_newsletter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Hm Newsletter block.
 *
 * @Block(
 *   id = "hm_newsletter_block",
 *   admin_label = @Translation("Harbourmaster newsletter subscription form"),
 * )
 */
class HmNewsletterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $configFactory;
  private $formElements = [
    'title', 'firstname', 'name', 'zipcode', 'location', 'birthdate',
  ];

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * HmNewsletterBlock constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $blockConfig = $this->getConfiguration();
    $settings = $this->configFactory->get('hm_newsletter.settings');

    $render = [
      '#theme' => 'hm_newsletter_form',
      '#attached' => array(
        'library' => array(
          'hm_newsletter/base',
        ),
        'drupalSettings' => array(
          'hm_newsletter' => array(
            'env' => $settings->get('hm_environment'),
            'clientid' => $settings->get('hm_client_id'),
          ),
        ),
      ),
    ];

    $this->preprocessBlockConfig($render, $blockConfig);
    $this->preprocessTemplateVariables($render, $settings, $blockConfig);

    return $render;
  }

  private function preprocessBlockConfig(&$vars, $blockConfig) {
    foreach ($this->formElements as $element) {
      if (isset($blockConfig[$element])) {
        $vars['#' . $element] = $blockConfig[$element];
      }
    }
  }

  private function preprocessTemplateVariables(&$vars, $settings, $blockConfig) {

    // Get newsletters.
    $newsletters = explode(PHP_EOL, $blockConfig['newsletters']);
    $newsletters_options = array();
    foreach ($newsletters as $newsletter) {
      $newsletter = explode('|', $newsletter);
      $newsletters_options[$newsletter[0]] = $newsletter[1];
    }
    $vars['#newsletters'] = $newsletters_options;

    $vars['#headline'] = $blockConfig['headline'];
    $vars['#text'] = $blockConfig['text']['value'];
    $vars['#confirmation_headline'] = $blockConfig['confirmation_headline'];
    $vars['#confirmation_text'] = $blockConfig['confirmation_text']['value'];

    // Privacy text.
    // @FIXME privacy text seems to be unused
    $hm_link_privacy = $settings->get('hm_link_privacy');
    if (!empty($hm_link_privacy)) {
//      $link = Link::fromTextAndUrl('AGB/Datenschutzbestimmungen', $hm_link_privacy);
//      $vars['#privacy_text'] = 'Ich stimme den ' . $link->toString() .' zu';
    }

    // Client id.
    $vars['#client_id'] = $settings->get('hm_client_id');

    // Imprint.
    $vars['#imprint_text'] = $settings->get('hm_imprint_text');

    // Birthday values.
    $birthday = array();
    // Days.
    $birthday['day'][] = '';
    foreach (range(1, 31) as $number) {
      $birthday['day'][$number] = $number . '.';
    }
    // Months.
    $birthday['month'][] = '';
    foreach (range(1, 12) as $number) {
      $birthday['month'][$number] = $number . '.';
    }
    // Years.
    $year = date('Y');
    $birthday['year'][] = '';
    foreach (range(($year - 100), ($year - 16)) as $number) {
      $birthday['year'][$number] = $number;
    }
    $vars['#birthday'] = $birthday;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form = parent::blockForm($form, $form_state);

    $form['hm_newsletter_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Anzuzeigende Elemente'),
    ];

    foreach ($this->formElements as $element) {
      $form['hm_newsletter_fieldset'][$element] = [
        '#type' => 'checkbox',
        '#title' => ucfirst($element),
//      '#title_display' => 'before',
        '#default_value' => (isset($config[$element])) ? $config[$element] : 1,
      ];
    }

    $form['hm_newsletter_fieldset_newsletters'] = [
      '#type' => 'fieldset',
      '#title' => t('Newsletters'),
      'newsletters' => array(
        '#title' => $this->t('Newsletters'),
        '#description' => $this->t('Enter one value per line, in the format key|label.
     The key consists of CLIENTID_NEWSLETTERID, and is used by the thsixty api. The label will be used in displayed values and edit forms.'),
        '#type' => 'textarea',
        '#default_value' => !empty($config['newsletters']) ? $config['newsletters'] : '',
        '#required' => TRUE,
      ),
    ];

    $form['hm_newsletter_fieldset_content'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Inhalt'),
      'headline' => array(
        '#type' => 'textfield',
        '#title' => t('Headline'),
        '#default_value' => !empty($config['headline']) ? $config['headline'] : '',
        '#size' => 256,
        '#maxlength' => 512,
        '#required' => TRUE,
      ),
      'text' => array(
        '#type' => 'text_format',
        '#title' => t('Text'),
        '#default_value' => !empty($config['text']) ? $config['text']['value'] : '',
        '#rows' => 8,
        '#cols' => 128,
      ),
      'confirmation_headline' => array(
        '#type' => 'textfield',
        '#title' => t('Confirmation headline'),
        '#default_value' => !empty($config['confirmation_headline']) ? $config['confirmation_headline'] : '',
        '#size' => 256,
        '#maxlength' => 512,
        '#required' => TRUE,
      ),
      'confirmation_text' => array(
        '#type' => 'text_format',
        '#title' => t('Confirmation text'),
        '#default_value' => !empty($config['confirmation_text']) ? $config['confirmation_text']['value'] : '',
        '#rows' => 8,
        '#cols' => 128,
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    foreach ($form_state->getValue('hm_newsletter_fieldset') as $key => $value) {
      $this->setConfigurationValue($key, $value);
    }
    foreach ($form_state->getValue('hm_newsletter_fieldset_newsletters') as $key => $value) {
      $this->setConfigurationValue($key, $value);
    }
    foreach ($form_state->getValue('hm_newsletter_fieldset_content') as $key => $value) {
      $this->setConfigurationValue($key, $value);
    }
  }

}
