<?php
/**
 * Created by PhpStorm.
 * User: timowelde
 * Date: 09.03.16
 * Time: 15:04
 */

namespace Drupal\hm_newsletter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
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
          'hm_newsletter/base'
        )
      )
    ];

    $this->preprocess_template_variables($render, $settings);

    return $render;
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form = parent::blockForm($form, $form_state);
    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
  }

  private function preprocess_template_variables(&$vars, $settings) {

    // Get newsletters.
    $newsletters = explode(PHP_EOL, $settings->get('hm_available_newsletters'));
    $newsletters_options = array();
    foreach ($newsletters as $newsletter) {
      $newsletter = explode('|', $newsletter);
      $newsletters_options[$newsletter[0]] = $newsletter[1];
    }
    $vars['#newsletters'] = $newsletters_options;

    // Privacy text
    // @FIXME privacy text seems to be unused
    $hm_link_privacy = $settings->get('hm_link_privacy');
    if (!empty($hm_link_privacy)) {
//      $link = Link::fromTextAndUrl('AGB/Datenschutzbestimmungen', $hm_link_privacy);
//      $vars['#privacy_text'] = 'Ich stimme den ' . $link->toString() .' zu';
    }

    // Client id.
    $vars['#client_id'] = $settings->get('hm_client_id');

    // Imprint
    $vars['#imprint_text'] = $settings->get('hm_imprint_text');

    // Birthday values.
    $birthday = array();
    // Days.
    $birthday['day'][] = '';
    foreach(range(1, 31) as $number) {
      $birthday['day'][$number] = $number . '.';
    }
    // Months.
    $birthday['month'][] = '';
    foreach(range(1, 12) as $number) {
      $birthday['month'][$number] = $number . '.';
    }
    // Years.
    $year = date('Y');
    $birthday['year'][] = '';
    foreach(range(($year-100) , ($year-16)) as $number) {
      $birthday['year'][$number] = $number;
    }
    $vars['#birthday'] = $birthday;
  }


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
}