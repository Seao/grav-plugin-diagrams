<?php
namespace Grav\Plugin;

use \Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class DiagramsPlugin extends Plugin
{
    protected $theme;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPageContentRaw' => ['onPageContentRaw', 0],
            'onTwigSiteVariables'   => ['onTwigSiteVariables', 0]
        ];
    }

    public function onPageContentRaw(Event $event)
    {
        // Variables
        $this->align = $this->config->get('plugins.diagrams.align');

        $page = $event['page'];
        $twig = $this->grav['twig'];
        $config = $this->mergeConfig($page);

        if ($config->get('enabled')) {

            // Get initial content
            $raw = $page->getRawContent();

            /*****************************
             * SEQUENCE PART
             */

            $matchSequence = function ($matches) use (&$page, &$twig, &$config) {
                // Get the matching content
                $search_sequence = $matches[0];

                // Remove the tab selector
                $search_sequence = str_replace("[sequence]", "", $search_sequence);
                $search_sequence = str_replace("[/sequence]", "", $search_sequence);

                // Creating the replacement structure
                $replace_header = "<div class=\"diagram\" style=\"text-align:".$this->align."\">";
                $replace_footer = "</div>";
                $replace_content = $search_sequence;
                $replace = "$replace_header" . "$replace_content" . "$replace_footer";

                return $replace;
            };

            $raw = $this->parseInjectSequence($raw, $matchSequence);

            /*****************************
             * FLOW PART
             */

            $matchFlow = function ($matches) use (&$page, &$twig, &$config) {
                static $cpt = 0;

                // Get the matching content
                $search_flow = $matches[0];

                // Remove the tab selector
                $search_flow = str_replace("[flow]", "", $search_flow);
                $search_flow = str_replace("[/flow]", "", $search_flow);

                // Creating the replacement structure
                $replace_header = "<div id=\"canvas_".$cpt."\" class=\"flow\" style=\"text-align:".$this->align."\">";
                $cpt++;
                $replace_footer = "</div>";
                $replace_content = $search_flow;
                $replace = "$replace_header" . "$replace_content" . "$replace_footer";

                return $replace;
            };

            $raw = $this->parseInjectFlow($raw, $matchFlow);

            /*****************************
             * MERMAID PART
             */

            $match_mermaid = function ($matches) use (&$page, &$twig, &$config) {
                // Get the matching content
                $search_mermaid = $matches[0];

                // Remove the tab selector
                $search_mermaid = str_replace("[mermaid]", "", $search_mermaid);
                $search_mermaid = str_replace("[/mermaid]", "", $search_mermaid);

                // Creating the replacement structure
                $replace_header = "<div class=\"mermaid\" style=\"text-align:".$this->align."\">";
                $replace_footer = "</div>";
                $replace_content = $search_mermaid;
                $replace = "$replace_header" . "$replace_content" . "$replace_footer";

                return $replace;
            };

            $raw = $this->parseInjectMermaid($raw, $match_mermaid);

            /*****************************
             * APPLY CHANGES
             */
            $page->setRawContent($raw);
        }
    }

    /**
     *  Applies a specific function to the result of the sequence's regexp
     */
    protected function parseInjectSequence($content, $function)
    {
        // Regular Expression for selection
        $regex = '/\[sequence\]([\s\S]*?)\[\/sequence\]/';
        return preg_replace_callback($regex, $function, $content);
    }

    /**
     *  Applies a specific function to the result of the flow's regexp
     */
    protected function parseInjectFlow($content, $function)
    {
        // Regular Expression for selection
        $regex = '/\[flow\]([\s\S]*?)\[\/flow\]/';
        return preg_replace_callback($regex, $function, $content);
    }

    /**
     *  Applies a specific function to the result of the flow's regexp
     */
    protected function parseInjectMermaid($content, $function)
    {
        // Regular Expression for selection
        $regex = '/\[mermaid\]([\s\S]*?)\[\/mermaid\]/';
        return preg_replace_callback($regex, $function, $content);
    }

    /**
     * Set needed ressources to display and convert charts
     */
    public function onTwigSiteVariables()
    {
        // Variables
        $this->theme = $this->config->get('plugins.diagrams.theme');
        $this->font_size = $this->config->get('plugins.diagrams.font.size');
        $this->font_color = $this->config->get('plugins.diagrams.font.color');
        $this->line_color = $this->config->get('plugins.diagrams.line.color');
        $this->element_color = $this->config->get('plugins.diagrams.line.color');
        $this->condition_yes = $this->config->get('plugins.diagrams.condition.yes');
        $this->condition_no = $this->config->get('plugins.diagrams.condition.no');
        $this->gantt_axis = $this->config->get('plugins.diagrams.gantt.axis');

        // Resources for the conversion
        $this->grav['assets']->addJs('plugin://diagrams/js/underscore-min.js');
        $this->grav['assets']->addJs('plugin://diagrams/js/lodash.min.js');
        $this->grav['assets']->addJs('plugin://diagrams/js/raphael-min.js');
        $this->grav['assets']->addJs('plugin://diagrams/js/sequence-diagram-min.js');
        $this->grav['assets']->addJs('plugin://diagrams/js/flowchart-latest.js');
        $this->grav['assets']->addJs('plugin://diagrams/js/mermaid.min.js');
        $this->grav['assets']->addCss('plugin://diagrams/css/mermaid.css');

        // Used to start the conversion of the div "diagram" when the page is loaded
        $init = "$(document).ready(function() {
                    mermaid.initialize({startOnLoad:true});
                    mermaid.ganttConfig = {
                      axisFormatter: [[\"".$this->gantt_axis."\", function (d){return d.getDay() == 1;}]]
                    };

                    $(\".diagram\").sequenceDiagram({theme: '".$this->theme."'});

                    var parent = document.getElementsByClassName(\"flow\");
                    for(var i=0;i<parent.length;i++) {
                        var data = parent[i].innerHTML.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
                        parent[i].innerHTML = \"\";
                        var chart = flowchart.parse(data);
                        chart.drawSVG('canvas_'+i, {
                            'font-size': ".$this->font_size.",
                            'font-color': '".$this->font_color."',
                            'line-color': '".$this->line_color."',
                            'element-color': '".$this->element_color."',
                            'yes-text': '".$this->condition_yes."',
                            'no-text': '".$this->condition_no."',

                            // More informations : http://flowchart.js.org
                            'flowstate' : {
                                'simple': {'fill' : '#FFFFFF'},
                                'positive': {'fill' : '#387EF5'},
                                'success': { 'fill' : '#9FF781'},
                                'invalid': {'fill' : '#FA8258'},
                                'calm': {'fill' : '#11C1F3'},
                                'royal': {'fill' : '#CF86E9'},
                                'energized': {'fill' : 'F3FD60'},
                            }
                        });
                    }
                 });";
        $this->grav['assets']->addInlineJs($init);
    }
}
