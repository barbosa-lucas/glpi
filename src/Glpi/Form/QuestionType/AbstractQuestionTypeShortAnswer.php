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
use Override;

/**
 * Short answers are single line inputs used to answer simple questions.
 */
abstract class AbstractQuestionTypeShortAnswer extends AbstractQuestionType
{
    /**
     * Specific input type for child classes
     *
     * @return string
     */
    abstract public function getInputType(): string;

    #[Override]
    public function getFormEditorJsOptions(): string
    {
        return <<<JS
            {
                "extractDefaultValue": function (question) {
                    const input = question.find('[data-glpi-form-editor-question-type-specific]')
                        .find('[name="default_value"], [data-glpi-form-editor-original-name="default_value"]');

                    return input.val();
                },
                "convertDefaultValue": function (question, old_type, value) {
                    // Only accept string values
                    if (typeof value !== 'string') {
                        return '';
                    }

                    const input = question.find('[data-glpi-form-editor-question-type-specific]')
                        .find('[name="default_value"], [data-glpi-form-editor-original-name="default_value"]');
                    if (old_type === 'Glpi\\\\Form\\\\QuestionType\\\\QuestionTypeLongText') {
                        // Create a temporary element to convert HTML to text
                        const element = document.createElement('div');
                        element.innerHTML = value;
                        input.val(element.firstChild.textContent);
                    } else {
                        input.val(value);
                    }

                    return input.val();
                }
            }
        JS;
    }

    #[Override]
    public function renderAdministrationTemplate(
        ?Question $question = null,
        ?string $input_prefix = null
    ): string {
        $template = <<<TWIG
            <input
                class="form-control mb-2"
                type="{{ input_type }}"
                name="default_value"
                placeholder="{{ input_placeholder }}"
                value="{{ question is not null ? question.fields.default_value : '' }}"
                aria-label="Default value"
            />
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'          => $question,
            'input_type'        => $this->getInputType(),
            'input_placeholder' => $this->getName(),
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(
        Question $question,
    ): string {
        $template = <<<TWIG
            <input
                type="{{ input_type }}"
                class="form-control"
                name="{{ question.getEndUserInputName() }}"
                value="{{ question.fields.default_value }}"
                {{ question.fields.is_mandatory ? 'required' : '' }}
            >
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'   => $question,
            'input_type' => $this->getInputType(),
        ]);
    }

    #[Override]
    public function renderAnswerTemplate($answer): string
    {
        $template = <<<TWIG
            <div class="form-control-plaintext">{{ answer }}</div>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'answer' => $answer,
        ]);
    }

    #[Override]
    public function getCategory(): QuestionTypeCategory
    {
        return QuestionTypeCategory::SHORT_ANSWER;
    }
}
