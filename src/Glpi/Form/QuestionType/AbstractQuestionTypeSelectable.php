<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Form\QuestionType;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Question;
use Html;
use Override;

/**
 * Short answers are single line inputs used to answer simple questions.
 */
abstract class AbstractQuestionTypeSelectable extends AbstractQuestionType
{
    #[Override]
    public function __construct()
    {
    }

    /**
     * Specific input type for child classes
     *
     * @param ?Question $question
     * @return string
     */
    abstract public function getInputType(?Question $question): string;

    /**
     * Get javascript to be added in the footer
     * Some twig variables are available:
     * - question: the question object
     * - input_type: the input type
     * - question_type: the question type class
     * - rand: a random number
     *
     * @return string
     */
    public function getFooterScript(): string
    {
        $js = <<<TWIG
            $(document).ready(function() {
                {% if question is not null %}
                    const container = $('div[data-glpi-form-editor-selectable-question-options="{{ rand }}"]');
                    new GlpiFormQuestionTypeSelectable('{{ input_type }}', container);
                {% else %}
                    $(document).on('glpi-form-editor-question-type-changed', function(e, question, type) {
                        if (type === '{{ question_type|escape('js') }}') {
                            const container = question.find('div[data-glpi-form-editor-selectable-question-options]');
                            new GlpiFormQuestionTypeSelectable('{{ input_type }}', container);
                        }
                    });
                {% endif %}
            });
TWIG;

        return $js;
    }

    #[Override]
    public function loadJavascriptFiles(): array
    {
        return ['js/form_question_selectable.js'];
    }

    #[Override]
    public function formatDefaultValueForDB(mixed $value): ?string
    {
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }

    #[Override]
    public function validateExtraDataInput(array $input): bool
    {
        // The input can not be empty, always have at least one option : the last one can be empty
        if (empty($input) || !isset($input['options'])) {
            return false;
        }

        return true;
    }

    #[Override]
    public function prepareExtraData(array $input): array
    {
        // The last option can be empty, so we need to remove it
        if (isset($input['options']) && end($input['options']) === '') {
            array_pop($input['options']);
        }

        return $input;
    }

    public function hideOptionsContainerWhenUnfocused(): bool
    {
        return false;
    }

    /**
     * Retrieve the options
     *
     * @param ?Question $question
     * @return array
     */
    public function getOptions(?Question $question): array
    {
        if ($question === null) {
            return [];
        }

        return $question->getExtraDatas()['options'] ?? [];
    }

    /**
     * Retrieve the values
     *
     * @param ?Question $question
     * @return array
     */
    public function getValues(?Question $question): array
    {
        // If the question is not set we return an empty array (no options per default)
        if ($question === null) {
            return [];
        }

        $values = [];
        $options = $this->getOptions($question);
        $default_values = explode(',', $question->fields['default_value'] ?? '');
        foreach ($options as $uuid => $option) {
            $values[] = [
                'uuid' => $uuid,
                'value' => $option,
                'checked' => (int) in_array($uuid, $default_values),
            ];
        }

        return $values;
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question = null): string
    {
        $template = <<<TWIG
        {% set rand = random() %}

        {% macro addOption(input_type, checked, value, placeholder, uuid = null, extra_details = false, disabled = false) %}
            {% if uuid is null %}
                {% set uuid = random() %}
            {% endif %}

            <div
                class="d-flex gap-1 align-items-center mb-2"
                {{ extra_details ? 'data-glpi-form-editor-question-extra-details' : '' }}
            >
                <i
                    role="button"
                    aria-label="{{ __('Move option') }}"
                    data-glpi-form-editor-question-extra-details
                    data-glpi-form-editor-question-option-handle
                    class="ti ti-grip-horizontal cursor-grab ms-auto me-1"
                    style="{{ disabled ? 'visibility: hidden;' : '' }}"
                    draggable="true"
                ></i>
                <input
                    type="{{ input_type }}"
                    name="default_value[]"
                    value="{{ uuid }}"
                    class="form-check-input" {{ checked ? 'checked' : '' }}
                    aria-label="{{ __('Default option') }}"
                    {{ disabled ? 'disabled' : '' }}
                >
                <input
                    data-glpi-form-editor-specific-question-extra-data
                    type="text"
                    class="w-full"
                    style="border: none transparent; outline: none; box-shadow: none;"
                    name="options[{{ uuid }}]"
                    value="{{ value }}"
                    placeholder="{{ placeholder }}"
                    aria-label="{{ __('Selectable option') }}"
                >
                <i
                    role="button"
                    aria-label="{{ __('Remove option') }}"
                    data-glpi-form-editor-question-extra-details
                    data-glpi-form-editor-question-option-remove
                    class="ti ti-x fa-lg text-muted ml-2 {{ value ? '' : 'd-none' }}"
                    style="cursor: pointer;"
                ></i>
            </div>
        {% endmacro %}

        <template>
            {{ _self.addOption(input_type, false, '', input_placeholder, null, true, true) }}
        </template>

        <div
            data-glpi-form-editor-selectable-question-options="{{ rand }}"
            {{ hide_container_when_unfocused ? 'data-glpi-form-editor-question-extra-details' : '' }}
        >
            {% for value in values %}
                {{ _self.addOption(input_type, value.checked, value.value, input_placeholder, value.uuid) }}
            {% endfor %}
        </div>

        {{ _self.addOption(input_type, false, '', input_placeholder, null, true, true) }}

        <script>
            {$this->getFooterScript()}
        </script>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'          => $question,
            'question_type'     => $this::class,
            'values'            => $this->getValues($question),
            'input_type'        => $this->getInputType($question),
            'input_placeholder' => __('Enter an option'),
            'hide_container_when_unfocused' => $this->hideOptionsContainerWhenUnfocused(),
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(
        Question $question,
    ): string {
        $template = <<<TWIG
            {% for value in values %}
                <label class="form-check">
                    <input
                        type="{{ input_type }}"
                        name="{{ question.getEndUserInputName() }}"
                        value="{{ value.value }}"
                        class="form-check-input" {{ value.checked ? 'checked' : '' }}
                    >
                    <span class="form-check-label">{{ value.value }}</span>
                </label>
            {% endfor %}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'   => $question,
            'values'     => $this->getValues($question),
            'input_type' => $this->getInputType($question),
        ]);
    }

    #[Override]
    public function renderAnswerTemplate($answers): string
    {
        $template = <<<TWIG
            {% for answer in answers %}
                <div class="form-control-plaintext">{{ answer }}</div>
            {% endfor %}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'answers' => $answers,
        ]);
    }
}
