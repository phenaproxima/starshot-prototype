<?php

namespace Drupal\starshot_installer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to choose the site template and optional add-on recipes.
 *
 * @todo Present this as a mini project browser once
 *   https://www.drupal.org/i/3450629 is fixed.
 */
final class RecipesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'starshot_installer_recipes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#title'] = $this->t('Choose template & add-ons');

    $form['template'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose your site template'),
      '#options' => [
        'starshot' => $this->t('Starshot'),
      ],
      '#required' => TRUE,
      '#default_value' => 'starshot',
    ];

    $options = [
      'starshot_multilingual' => $this->t('Multilingual support'),
      'starshot_accessibility_tools' => $this->t('Accessibility tools'),
    ];

    $form['add_ons'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Optional add-ons'),
      '#options' => $options,
      '#default_value' => [],
    ];

    $form['actions'] = [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save and continue'),
        '#button_type' => 'primary',
      ],
      '#type' => 'actions',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    global $install_state;

    $add_ons = $form_state->getValue('add_ons', []);
    $add_ons = array_filter($add_ons);

    $install_state['parameters']['recipes'] = [
      $form_state->getValue('template', 'starshot'),
      ...array_values($add_ons),
    ];
  }

}
