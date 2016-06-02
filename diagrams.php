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

            $raw = $page->getRawContent();

            $function = function ($matches) use (&$page, &$twig, &$config) {
                // Get the matching content
                $search = $matches[0];

                // Remove the tab selector
                $search = str_replace("[sequence]", "", $search);
                $search = str_replace("[/sequence]", "", $search);

                // Creating the replacement structure
                $replace_header = "<div class=\"diagram\" style=\"text-align:".$this->align."\">";
                $replace_footer = "</div>";
                $replace_content = $search;
                $replace = "$replace_header" . "$replace_content" . "$replace_footer";
                
                return $replace;
            };

            $page->setRawContent($this->parseInjectLinks($raw, $function));
        }
    }

    /**
     *  Applies a specific function to the result of the regexp
     */
    protected function parseInjectLinks($content, $function)
    {
        // Regular Expression for selection
        $regex = '/\[sequence\][^\"]*\[\/sequence\]/';
        return preg_replace_callback($regex, $function, $content);
    }

    /**
     * Set needed ressources to display and convert charts
     */
    public function onTwigSiteVariables()
    {
        // Variables
        $this->theme = $this->config->get('plugins.diagrams.theme');

        // Resources for the conversion
        $this->grav['assets']->addJs('plugin://diagrams/js/underscore-min.js');
        $this->grav['assets']->addJs('plugin://diagrams/js/lodash.min.js');
        $this->grav['assets']->addJs('plugin://diagrams/js/raphael-min.js');
        $this->grav['assets']->addJs('plugin://diagrams/js/sequence-diagram-min.js');

        $this->theme = $this->config->get('plugins.diagrams.theme');

        // Used to start the conversion of the div "diagram" when the page is loaded
        $init = "$(document).ready(function() {
                    $(\".diagram\").sequenceDiagram({theme: '".$this->theme."'});
                 });";
        $this->grav['assets']->addInlineJs($init);
    }
}