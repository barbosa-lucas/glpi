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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\ConfigFieldInterface;
use Glpi\Form\Form;
use Glpi\Form\Tag\FormTagsManager;
use Override;

class ContentField implements ConfigFieldInterface
{
    #[Override]
    public function getKey(): string
    {
        return 'content';
    }

    #[Override]
    public function getLabel(): string
    {
        return __("Content");
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        mixed $configurated_value,
        string $input_name,
        array $display_options
    ): string {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.textareaField(
                input_name,
                value,
                label,
                options|merge({
                    'enable_richtext': true,
                    'enable_images': false,
                    'enable_form_tags': true,
                    'form_tags_form_id': form_id
                })
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'form_id'    => $form->fields['id'],
            'label'      => $this->getLabel(),
            'value'      => $configurated_value ?? '',
            'input_name' => $input_name,
            'options'    => $display_options,
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        mixed $configurated_value,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (is_null($configurated_value)) {
            return $input;
        }

        $tag_manager = new FormTagsManager();
        $input['content'] = $tag_manager->insertTagsContent(
            $configurated_value,
            $answers_set
        );

        return $input;
    }
}
