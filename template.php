<?php

    /**
     * Basic Template Class
     * @author      Will
     * @optional    Mustache.php
     *
     * @description Template class that works with both .php and .mustache template files
     *
     */

    namespace willwashburn;

    Class template
    {

        /*
         * Default location of the layouts & partials
         */
        protected $root = ROOT_PATH;
        public $layout_path = 'views/layouts/', $partial_path = 'views/partials/';

        /*
         * If this is set to true, there will be no error if the template doesn't exist
         */
        public $soft_fetch = false;

        /*
         *Private vars
         */
        protected $vars = array();
        protected $mustache;
        private static $instance;


        /**
         * Gets an instance of the template
         *
         * @author  Will
         */
        public static function getInstance()
        {

            if (!self::$instance) {
                self::$instance = new template();
            }

            return self::$instance;
        }

        /**
         * Construct
         *
         * @author Will
         */
        function __construct()
        {
            if (class_exists('\Mustache_Engine')) {

                $this->mustache = new \Mustache_Engine(array(
                    'escape'          => function ($value) {
                        return $value;
                    },
                    'partials_loader' => new \Mustache_Loader_FilesystemLoader(__APP_PATH . '/views/html/')
                ));
            } else {

                $this->mustache = false;
            }

        }

        /**
         * Magic Set
         * @author      Will
         * @description adds to the var array so it can be used later
         *
         * @param $index
         * @param $value
         */
        public function __set($index, $value)
        {
            $this->vars[$index] = $value;
        }

        /**
         * Determine if mustache or php
         * @author Will
         *
         * @param $filename
         * @return string
         */
        protected function template_type($filename)
        {
            $filename_parts = explode('.', $filename);
            if (isset($filename_parts[1])) {
                switch ($filename_parts[1]) {
                    case 'mustache':
                    case 'mstche':
                    case 'ms':
                        return 'mustache';
                        break;
                    case 'php':
                        return 'php';
                        break;
                }
            }

            return 'php';
        }

        /**
         * Show / Print / echo
         *
         * @description print the template
         *
         * @author Will
         */
        function show($filename, $override_template_path = false)
        {

            $template_system = $this->template_type($filename);

            if ($override_template_path) {

                $full_path = $override_template_path . $filename;

            } else {

                $full_path = $this->root . '/' . $this->layout_path . $filename;

            }

            if (file_exists($full_path) == false) {
                throw new TemplateException('Template not found in ' . $full_path);
            } else {
                switch ($template_system) {

                    case 'php':

                        foreach ($this->vars as $key => $value) {
                            $$key = $value;
                        }

                        include $full_path;

                        return true;
                        break;

                    case 'mustache':

                        $template = file_get_contents($full_path);
                        echo $this->mustache->render($template, $this->vars);

                        return true;

                        break;

                }
            }

            return false;
        }

        /**
         * Fetch resulting html
         *
         * @author              Will
         * @description         finds the view, includes some variables and returns it via output buffer
         *
         */
        public function fetch($partial_filename, $override_partial_path = false)
        {
            if (!$override_partial_path) {
                $path = $this->root.'/'.$this->partial_path;
            } else {
                $path = $override_partial_path;
            }

            $template_system = $this->template_type($partial_filename);

            $full_path = $path . $partial_filename;
            if (file_exists($full_path)) {
                switch ($template_system) {
                    case 'mustache':
                        $template = file_get_contents($full_path);
                        $output   = $this->mustache->render($template, $this->vars);
                        break;
                    case 'php':
                        $output = $this->get_output($full_path);
                        break;
                }
            } else {
                if ($this->soft_fetch) {
                    return '';
                } else {
                    throw new TemplateException("The template file '$partial_filename' does not exist (found at $full_path)");
                }
            }

            return isset($output) ? $output : false;
        }

        /**
         * Alias for fetch
         * @author Will
         *
         * @param $partial_filename
         * @param $override_partial_path
         * @return mixed
         */
        public function render($partial_filename, $override_partial_path = false)
        {
            return $this->fetch($partial_filename, $override_partial_path);
        }

        /**
         * Render
         *
         * @param $template_file
         * @return bool|string
         * @throws TemplateException
         */
        private function get_output($template_file)
        {
            extract($this->vars);

            if (file_exists($template_file)) {
                ob_start();
                include($template_file);
                $output = ob_get_contents();
                ob_end_clean();
            } else {

                if ($this->soft_fetch) {
                    return '';
                } else {
                    throw new TemplateException("The template file '$template_file' does not exist");
                }
            }

            return !empty($output) ? $output : false;
        }

    }

    class TemplateException extends \Exception
    {

    }
