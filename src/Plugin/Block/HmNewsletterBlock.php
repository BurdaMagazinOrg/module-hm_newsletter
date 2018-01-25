<?php

namespace Drupal\hm_newsletter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
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

	/**
	 * @var ConfigFactory $configFactory
	 */
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
		/**
		 * @var ImmutableConfig $settings
		 */
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
						'displayed_agreements' => $settings->get('hm_displayed_agreements'),
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
		$vars['#source'] = $blockConfig['source'];
		$vars['#privacy'] = $blockConfig['privacy'];
		$vars['#optin'] = $blockConfig['optin'];
		$vars['#submit_label'] = $blockConfig['submit_label'];
		$vars['#email'] = $blockConfig['email'];

		// Privacy text.
		// @FIXME privacy text seems to be unused
//    $hm_link_privacy = $settings->get('hm_link_privacy');
//    if (!empty($hm_link_privacy)) {
//      $link = Link::fromTextAndUrl('AGB/Datenschutzbestimmungen', $hm_link_privacy);
//      $vars['#privacy_text'] = 'Ich stimme den ' . $link->toString() .' zu';
//    }

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


		/****************************************************************
		 *** General Settings for registration
		 ****************************************************************/
		$form['hm_newsletter_general_settings'] = [
			'#type' => 'fieldset',
			'#title' => $this->t('General settings'),
		];

		$form['hm_newsletter_general_settings']['source'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Source'),
			'#default_value' => isset($config['source']) ? $config['source'] : '',
			'#maxlength' => 512,
			'#required' => TRUE,
			'#description' => 'This will help to identify where the registration came from, use a meaningful descriptor. Example: "cinema_website_newsletter_sidebar"'
		];

		$form['hm_newsletter_general_settings']['privacy'] = [
			'#type' => 'select',
			'#title' => $this->t('Privacy settings'),
			'#options' => [
				'off' => $this->t('off'),
				'optional' => $this->t('optional'),
				'required' => $this->t('required'),
			],
			'#description' => 'Display settings for the checkbox starting with: "Ich willige widerruflich ein, dass die hier aufgeführten Unternehmen ..."',
			'#default_value' => isset($config['privacy']) ? $config['privacy'] : 'off'
		];

		$form['hm_newsletter_general_settings']['optin'] = [
			'#type' => 'select',
			'#title' => $this->t('Opt-In settings'),
			'#options' => [
				'off' => $this->t('off'),
				'optional' => $this->t('optional'),
				'required' => $this->t('required'),
			],
			'#description' => 'Display settings for the checkbox starting with: "Ja, ich bin damit einverstanden, dass mich Burda Direkt Services GmbH ..."',
			'#default_value' => isset($config['optin']) ? $config['optin'] : 'off'
		];

		$form['hm_newsletter_general_settings']['newsletters'] = [
			'#title' => $this->t('Newsletters'),
			'#description' => $this->t('Enter one value per line, in the format key|label.
	The key consists of CLIENTID_NEWSLETTERID, and is used by the thsixty api. The label will be used in displayed values and edit forms.'),
			'#type' => 'textarea',
			'#default_value' => !empty($config['newsletters']) ? $config['newsletters'] : '',
			'#required' => TRUE,
		];

		$form['hm_newsletter_general_settings']['submit_label'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Submit button text'),
			'#default_value' => !empty($config['submit_label']) ? $config['submit_label'] : 'Anmelden'
		];


		/****************************************************************
		 *** Settings for visible fields and their labels/placeholders
		 ****************************************************************/
		$form['hm_newsletter_fieldset'] = [
			'#type' => 'fieldset',
			'#title' => $this->t('Visible fields'),
		];

		foreach ($this->formElements as $element) {
			$form['hm_newsletter_fieldset'][$element] = [
				'#type' => 'fieldset',
				'#title' => ucfirst($element),
				'is_visible' => [
					'#type' => 'checkbox',
					'#title' => $this->t('display'),
					'#default_value' => (isset($config[$element]['is_visible'])) ? $config[$element]['is_visible'] : 1,
				],
				'label_display' => [
					'#type' => 'checkboxes',
					'#options' => [
						'label' => $this->t('Display label for the field'),
						'placeholder' => $this->t('Display placeholder inside the input field')
					],
					'#default_value' => [
						isset($config[$element]['label_display']['label']) ? $config[$element]['label_display']['label'] : '',
						isset($config[$element]['label_display']['placeholder']) ? $config[$element]['label_display']['placeholder'] : '',
					],
					'#title' => $this->t('Field label settings'),
					'#states' => [
						'visible' => [
							':input[name="settings[hm_newsletter_fieldset]['.$element.'][is_visible]"]' => ['checked' => TRUE],
						],
					],
				],
				'label_text' => [
					'#type' => 'textfield',
					'#title' => $this->t('Label'),
					'#default_value' => !empty($config[$element]['label_text']) ? $config[$element]['label_text'] : '',
					'#states' => [
						'visible' => [
							':input[name="settings[hm_newsletter_fieldset]['.$element.'][label_display][label]"]' => ['checked' => TRUE],
						],
					],
				],
				'placeholder_text' => [
					'#type' => 'textfield',
					'#title' => $this->t('Placeholder'),
					'#default_value' => !empty($config[$element]['placeholder_text']) ? $config[$element]['placeholder_text'] : '',
					'#states' => [
						'visible' => [
							':input[name="settings[hm_newsletter_fieldset]['.$element.'][label_display][placeholder]"]' => ['checked' => TRUE],
						],
					],
				]
			];
		}

		$form['hm_newsletter_fieldset']['email'] = [
			'#type' => 'fieldset',
			'#title' => $this->t('E-Mail'),
			'is_visible' => [
				'#type' => 'hidden',
				'#default_value' => 1,
			],
			'label_display' => [
				'#type' => 'checkboxes',
				'#options' => [
					'label' => $this->t('Display label for the field'),
					'placeholder' => $this->t('Display placeholder inside the input field')
				],
				'#default_value' => [
					isset($config['email']['label_display']['label']) ? $config['email']['label_display']['label'] : '',
					isset($config['email']['label_display']['placeholder']) ? $config['email']['label_display']['placeholder'] : '',
				],
				'#title' => $this->t('Field label settings'),
				'#states' => [
					'visible' => [
						':input[name="settings[hm_newsletter_fieldset][email][is_visible]"]' => ['value' => 1],
					],
				],
			],
			'label_text' => [
				'#type' => 'textfield',
				'#title' => $this->t('Label'),
				'#default_value' => !empty($config['email']['label_text']) ? $config['email']['label_text'] : 'E-Mail:',
				'#states' => [
					'visible' => [
						':input[name="settings[hm_newsletter_fieldset][email][label_display][label]"]' => ['checked' => TRUE],
					],
				],
			],
			'placeholder_text' => [
				'#type' => 'textfield',
				'#title' => $this->t('Placeholder'),
				'#default_value' => !empty($config['email']['placeholder_text']) ? $config['email']['placeholder_text'] : '',
				'#states' => [
					'visible' => [
						':input[name="settings[hm_newsletter_fieldset][email][label_display][placeholder]"]' => ['checked' => TRUE],
					],
				],
			]
		];


		/****************************************************************
		 *** Settings for texts
		 ****************************************************************/
		$form['hm_newsletter_fieldset_content'] = [
			'#type' => 'fieldset',
			'#title' => $this->t('Inhalt'),
			'headline' => array(
				'#type' => 'textfield',
				'#title' => t('Headline'),
				'#default_value' => !empty($config['headline']) ? $config['headline'] : '',
				'#size' => 256,
				'#maxlength' => 512
			),
			'text' => array(
				'#type' => 'text_format',
				'#title' => t('Text'),
				'#format' => 'full_html',
				'#default_value' => !empty($config['text']) ? $config['text']['value'] : '',
				'#rows' => 8,
				'#cols' => 128
			),
			'confirmation_headline' => array(
				'#type' => 'textfield',
				'#title' => t('Confirmation headline'),
				'#default_value' => !empty($config['confirmation_headline']) ? $config['confirmation_headline'] : '',
				'#size' => 256,
				'#maxlength' => 512
			),
			'confirmation_text' => array(
				'#type' => 'text_format',
				'#format' => 'full_html',
				'#title' => t('Confirmation text'),
				'#default_value' => !empty($config['confirmation_text']) ? $config['confirmation_text']['value'] : '',
				'#rows' => 8,
				'#cols' => 128
			),
		];

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function blockSubmit($form, FormStateInterface $form_state) {
		parent::blockSubmit($form, $form_state);

		foreach ($form_state->getValue('hm_newsletter_general_settings') as $key => $value) {
			$this->setConfigurationValue($key, $value);
		}
		foreach ($form_state->getValue('hm_newsletter_fieldset') as $key => $value) {
			$this->setConfigurationValue($key, $value);
		}
		foreach ($form_state->getValue('hm_newsletter_fieldset_content') as $key => $value) {
			$this->setConfigurationValue($key, $value);
		}
	}

}
