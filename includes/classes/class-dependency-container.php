<?php
/**
 * Dependency Injection Container Class
 *
 * @package    Askro
 * @subpackage Core/DI
 * @since      1.0.0
 * @author     Arashdi <arashdi@wratcliff.dev>
 * @copyright  2025 William Ratcliff
 * @license    GPL-3.0-or-later
 * @link       https://arashdi.com
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro Dependency Injection Container Class
 *
 * Manages component dependencies and provides dependency injection
 * to reduce coupling between components.
 *
 * @since 1.0.0
 */
class Askro_Dependency_Container {

    /**
     * Container instance
     *
     * @var Askro_Dependency_Container
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Registered services
     *
     * @var array
     * @since 1.0.0
     */
    private $services = [];

    /**
     * Service instances
     *
     * @var array
     * @since 1.0.0
     */
    private $instances = [];

    /**
     * Get container instance
     *
     * @return Askro_Dependency_Container
     * @since 1.0.0
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a service
     *
     * @param string $name Service name
     * @param callable $factory Service factory function
     * @param bool $singleton Whether this is a singleton service
     * @since 1.0.0
     */
    public function register($name, $factory, $singleton = true) {
        $this->services[$name] = [
            'factory' => $factory,
            'singleton' => $singleton
        ];
    }

    /**
     * Get a service instance
     *
     * @param string $name Service name
     * @return mixed Service instance
     * @since 1.0.0
     */
    public function get($name) {
        if (!isset($this->services[$name])) {
            throw new Exception("Service '{$name}' not registered");
        }

        $service = $this->services[$name];

        // Return existing instance if singleton
        if ($service['singleton'] && isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // Create new instance
        $instance = call_user_func($service['factory'], $this);

        // Store instance if singleton
        if ($service['singleton']) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    /**
     * Check if service is registered
     *
     * @param string $name Service name
     * @return bool
     * @since 1.0.0
     */
    public function has($name) {
        return isset($this->services[$name]);
    }

    /**
     * Remove a service
     *
     * @param string $name Service name
     * @since 1.0.0
     */
    public function remove($name) {
        unset($this->services[$name]);
        unset($this->instances[$name]);
    }

    /**
     * Clear all services
     *
     * @since 1.0.0
     */
    public function clear() {
        $this->services = [];
        $this->instances = [];
    }

    /**
     * Get all registered service names
     *
     * @return array
     * @since 1.0.0
     */
    public function get_services() {
        return array_keys($this->services);
    }

    /**
     * Initialize core services
     *
     * @since 1.0.0
     */
    public function initialize_core_services() {
        // Register core services
        $this->register('database', function($container) {
            return new Askro_Database();
        });

        $this->register('assets', function($container) {
            return new Askro_Assets();
        });

        $this->register('display', function($container) {
            return new Askro_Display();
        });

        $this->register('forms', function($container) {
            return new Askro_Forms();
        });

        $this->register('comments', function($container) {
            return new Askro_Comments();
        });

        $this->register('voting', function($container) {
            return new Askro_Voting();
        });

        $this->register('gamification', function($container) {
            return new Askro_Gamification();
        });

        $this->register('security', function($container) {
            return new Askro_Security();
        });

        $this->register('notifications', function($container) {
            return new Askro_Notifications();
        });

        $this->register('analytics', function($container) {
            return new Askro_Analytics();
        });

        $this->register('leaderboard', function($container) {
            return new Askro_Leaderboard();
        });

        $this->register('post_types', function($container) {
            return new Askro_Post_Types();
        });

        $this->register('taxonomies', function($container) {
            return new Askro_Taxonomies();
        });

        $this->register('shortcodes', function($container) {
            return new Askro_Shortcodes();
        });

        $this->register('url_handler', function($container) {
            return new Askro_URL_Handler();
        });

        // Register API services
        $this->register('api', function($container) {
            return new Askro_API();
        });

        $this->register('api_cache', function($container) {
            return new Askro_API_Cache();
        });

        $this->register('api_auth', function($container) {
            return new Askro_API_Auth();
        });

        $this->register('api_docs', function($container) {
            return new Askro_API_Docs();
        });

        // Register AJAX handlers
        $this->register('ajax_voting', function($container) {
            return new Askro_Ajax_Voting();
        });

        $this->register('ajax_comments', function($container) {
            return new Askro_Ajax_Comments();
        });

        $this->register('ajax_search', function($container) {
            return new Askro_Ajax_Search();
        });

        // Register admin service
        if (defined('ABSPATH') && function_exists('is_admin') && is_admin()) {
            $this->register('admin', function($container) {
                return new Askro_Admin();
            });
        }
    }

    /**
     * Initialize all services
     *
     * @since 1.0.0
     */
    public function initialize_all_services() {
        $this->initialize_core_services();

        // Initialize all registered services
        foreach ($this->get_services() as $service_name) {
            $service = $this->get($service_name);
            if (method_exists($service, 'init')) {
                $service->init();
            }
        }
    }

    /**
     * Get service with dependencies injected
     *
     * @param string $name Service name
     * @param array $dependencies Additional dependencies
     * @return mixed Service instance
     * @since 1.0.0
     */
    public function get_with_dependencies($name, $dependencies = []) {
        $service = $this->get($name);

        // Inject dependencies if service supports it
        if (method_exists($service, 'set_dependencies')) {
            $service->set_dependencies($dependencies);
        }

        return $service;
    }

    /**
     * Create a service with constructor dependencies
     *
     * @param string $class_name Class name
     * @param array $dependencies Constructor dependencies
     * @return object Service instance
     * @since 1.0.0
     */
    public function create($class_name, $dependencies = []) {
        $resolved_dependencies = [];

        foreach ($dependencies as $dependency) {
            if (is_string($dependency) && $this->has($dependency)) {
                $resolved_dependencies[] = $this->get($dependency);
            } else {
                $resolved_dependencies[] = $dependency;
            }
        }

        return new $class_name(...$resolved_dependencies);
    }
} 
