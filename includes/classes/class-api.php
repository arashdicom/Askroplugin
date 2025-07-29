<?php
/**
 * API Handler Class
 *
 * @package    Askro
 * @subpackage Core/API
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
 * Askro API Class
 *
 * Handles all REST API endpoints and responses
 *
 * @since 1.0.0
 */
class Askro_API {

    /**
     * API namespace
     *
     * @var string
     * @since 1.0.0
     */
    private $namespace = 'askro/v1';

    /**
     * API base URL
     *
     * @var string
     * @since 1.0.0
     */
    private $api_base = 'askro-api';

    /**
     * Rate limiting settings
     *
     * @var array
     * @since 1.0.0
     */
    private $rate_limits = [
        'questions' => ['requests' => 100, 'window' => 3600],
        'answers' => ['requests' => 50, 'window' => 3600],
        'votes' => ['requests' => 200, 'window' => 3600],
        'comments' => ['requests' => 100, 'window' => 3600],
        'search' => ['requests' => 300, 'window' => 3600]
    ];

    /**
     * Initialize the API component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('wp_loaded', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_api_requests']);
        
        // Add CORS headers
        add_action('init', [$this, 'add_cors_headers']);
        
        // Add rate limiting
        add_action('rest_api_init', [$this, 'add_rate_limiting']);
    }

    /**
     * Register REST API routes
     *
     * @since 1.0.0
     */
    public function register_routes() {
        // Questions endpoints
        register_rest_route($this->namespace, '/questions', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_questions'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => $this->get_questions_args()
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_question'],
                'permission_callback' => [$this, 'check_create_permission'],
                'args' => $this->get_create_question_args()
            ]
        ]);

        register_rest_route($this->namespace, '/questions/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_question'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => [
                    'id' => [
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_question'],
                'permission_callback' => [$this, 'check_edit_permission'],
                'args' => $this->get_update_question_args()
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_question'],
                'permission_callback' => [$this, 'check_delete_permission']
            ]
        ]);

        // Answers endpoints
        register_rest_route($this->namespace, '/questions/(?P<question_id>\d+)/answers', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_answers'],
                'permission_callback' => [$this, 'check_read_permission']
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_answer'],
                'permission_callback' => [$this, 'check_create_permission'],
                'args' => $this->get_create_answer_args()
            ]
        ]);

        register_rest_route($this->namespace, '/answers/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_answer'],
                'permission_callback' => [$this, 'check_read_permission']
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_answer'],
                'permission_callback' => [$this, 'check_edit_permission']
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_answer'],
                'permission_callback' => [$this, 'check_delete_permission']
            ]
        ]);

        // Voting endpoints
        register_rest_route($this->namespace, '/posts/(?P<post_id>\d+)/vote', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'cast_vote'],
                'permission_callback' => [$this, 'check_vote_permission'],
                'args' => $this->get_vote_args()
            ]
        ]);

        // Comments endpoints
        register_rest_route($this->namespace, '/posts/(?P<post_id>\d+)/comments', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_comments'],
                'permission_callback' => [$this, 'check_read_permission']
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_comment'],
                'permission_callback' => [$this, 'check_create_permission'],
                'args' => $this->get_create_comment_args()
            ]
        ]);

        // Search endpoints
        register_rest_route($this->namespace, '/search', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'search_content'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => $this->get_search_args()
            ]
        ]);

        // User endpoints
        register_rest_route($this->namespace, '/users/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_user'],
                'permission_callback' => [$this, 'check_read_permission']
            ]
        ]);

        register_rest_route($this->namespace, '/users/me', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_current_user'],
                'permission_callback' => [$this, 'check_authenticated']
            ]
        ]);

        // Analytics endpoints
        register_rest_route($this->namespace, '/analytics', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_analytics'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);

        // Leaderboard endpoints
        register_rest_route($this->namespace, '/leaderboard', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_leaderboard'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => $this->get_leaderboard_args()
            ]
        ]);
    }

    /**
     * Get questions endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function get_questions($request) {
        try {
            $this->check_rate_limit('questions');
            
            $args = [
                'post_type' => 'askro_question',
                'post_status' => 'publish',
                'posts_per_page' => $request->get_param('per_page') ?: 15,
                'paged' => $request->get_param('page') ?: 1,
                'orderby' => $request->get_param('orderby') ?: 'date',
                'order' => $request->get_param('order') ?: 'DESC'
            ];

            // Add filters
            if ($request->get_param('category')) {
                $args['tax_query'][] = [
                    'taxonomy' => 'askro_category',
                    'field' => 'slug',
                    'terms' => $request->get_param('category')
                ];
            }

            if ($request->get_param('tag')) {
                $args['tax_query'][] = [
                    'taxonomy' => 'askro_tag',
                    'field' => 'slug',
                    'terms' => $request->get_param('tag')
                ];
            }

            if ($request->get_param('status')) {
                $args['meta_query'][] = [
                    'key' => 'askro_status',
                    'value' => $request->get_param('status')
                ];
            }

            $query = new WP_Query($args);
            $questions = [];

            foreach ($query->posts as $post) {
                $questions[] = $this->format_question($post);
            }

            $response = [
                'success' => true,
                'data' => $questions,
                'pagination' => [
                    'current_page' => $args['paged'],
                    'total_pages' => $query->max_num_pages,
                    'total_items' => $query->found_posts,
                    'per_page' => $args['posts_per_page']
                ]
            ];

            return new WP_REST_Response($response, 200);

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get single question endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function get_question($request) {
        try {
            $question_id = $request->get_param('id');
            $question = get_post($question_id);

            if (!$question || $question->post_type !== 'askro_question') {
                return $this->error_response('السؤال غير موجود', 404);
            }

            $formatted_question = $this->format_question($question, true);
            
            return new WP_REST_Response([
                'success' => true,
                'data' => $formatted_question
            ], 200);

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Create question endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function create_question($request) {
        try {
            $this->check_rate_limit('questions');
            
            $user_id = get_current_user_id();
            if (!$user_id) {
                return $this->error_response('يجب تسجيل الدخول', 401);
            }

            $title = sanitize_text_field($request->get_param('title'));
            $content = wp_kses_post($request->get_param('content'));
            $category = sanitize_text_field($request->get_param('category'));
            $tags = $request->get_param('tags');
            $status = sanitize_text_field($request->get_param('status')) ?: 'open';

            // Validation
            if (empty($title) || strlen($title) < 10) {
                return $this->error_response('عنوان السؤال يجب أن يكون 10 أحرف على الأقل', 400);
            }

            if (empty($content) || strlen($content) < 20) {
                return $this->error_response('محتوى السؤال يجب أن يكون 20 حرف على الأقل', 400);
            }

            // Create question
            $question_data = [
                'post_title' => $title,
                'post_content' => $content,
                'post_type' => 'askro_question',
                'post_status' => 'publish',
                'post_author' => $user_id
            ];

            $question_id = wp_insert_post($question_data);

            if (is_wp_error($question_id)) {
                return $this->error_response('فشل في إنشاء السؤال', 500);
            }

            // Set meta data
            update_post_meta($question_id, 'askro_status', $status);

            // Set categories and tags
            if ($category) {
                wp_set_object_terms($question_id, $category, 'askro_category');
            }

            if ($tags && is_array($tags)) {
                wp_set_object_terms($question_id, $tags, 'askro_tag');
            }

            // Award points
            askro_award_points($user_id, 10, 'طرح سؤال جديد');

            $question = get_post($question_id);
            $formatted_question = $this->format_question($question);

            return new WP_REST_Response([
                'success' => true,
                'data' => $formatted_question,
                'message' => 'تم إنشاء السؤال بنجاح'
            ], 201);

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get answers endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function get_answers($request) {
        try {
            $question_id = $request->get_param('question_id');
            $question = get_post($question_id);

            if (!$question || $question->post_type !== 'askro_question') {
                return $this->error_response('السؤال غير موجود', 404);
            }

            $args = [
                'post_type' => 'askro_answer',
                'post_status' => 'publish',
                'meta_query' => [
                    [
                        'key' => 'askro_question_id',
                        'value' => $question_id
                    ]
                ],
                'posts_per_page' => $request->get_param('per_page') ?: 20,
                'paged' => $request->get_param('page') ?: 1,
                'orderby' => 'meta_value_num',
                'meta_key' => 'askro_is_best_answer',
                'order' => 'DESC'
            ];

            $query = new WP_Query($args);
            $answers = [];

            foreach ($query->posts as $post) {
                $answers[] = $this->format_answer($post);
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => $answers,
                'pagination' => [
                    'current_page' => $args['paged'],
                    'total_pages' => $query->max_num_pages,
                    'total_items' => $query->found_posts,
                    'per_page' => $args['posts_per_page']
                ]
            ], 200);

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Create answer endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function create_answer($request) {
        try {
            $this->check_rate_limit('answers');
            
            $user_id = get_current_user_id();
            if (!$user_id) {
                return $this->error_response('يجب تسجيل الدخول', 401);
            }

            $question_id = $request->get_param('question_id');
            $content = wp_kses_post($request->get_param('content'));

            // Validation
            if (!$question_id || !get_post($question_id)) {
                return $this->error_response('السؤال غير موجود', 404);
            }

            if (empty($content) || strlen($content) < 10) {
                return $this->error_response('محتوى الإجابة يجب أن يكون 10 أحرف على الأقل', 400);
            }

            // Check if user already answered
            $existing_answer = get_posts([
                'post_type' => 'askro_answer',
                'post_author' => $user_id,
                'meta_query' => [
                    [
                        'key' => 'askro_question_id',
                        'value' => $question_id
                    ]
                ]
            ]);

            if (!empty($existing_answer)) {
                return $this->error_response('لقد أجبت على هذا السؤال مسبقاً', 400);
            }

            // Create answer
            $answer_data = [
                'post_title' => 'إجابة على السؤال',
                'post_content' => $content,
                'post_type' => 'askro_answer',
                'post_status' => 'publish',
                'post_author' => $user_id
            ];

            $answer_id = wp_insert_post($answer_data);

            if (is_wp_error($answer_id)) {
                return $this->error_response('فشل في إنشاء الإجابة', 500);
            }

            // Set meta data
            update_post_meta($answer_id, 'askro_question_id', $question_id);
            update_post_meta($answer_id, 'askro_is_best_answer', 0);

            // Award points
            askro_award_points($user_id, 20, 'إجابة على سؤال');

            $answer = get_post($answer_id);
            $formatted_answer = $this->format_answer($answer);

            return new WP_REST_Response([
                'success' => true,
                'data' => $formatted_answer,
                'message' => 'تم إنشاء الإجابة بنجاح'
            ], 201);

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Cast vote endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function cast_vote($request) {
        try {
            $this->check_rate_limit('votes');
            
            $user_id = get_current_user_id();
            if (!$user_id) {
                return $this->error_response('يجب تسجيل الدخول', 401);
            }

            $post_id = $request->get_param('post_id');
            $vote_type = $request->get_param('vote_type');
            $vote_value = $request->get_param('vote_value') ?: 1;

            // Validation
            if (!$post_id || !get_post($post_id)) {
                return $this->error_response('المنشور غير موجود', 404);
            }

            if (!in_array($vote_type, ['useful', 'innovative', 'well_researched', 'incorrect', 'redundant'])) {
                return $this->error_response('نوع التصويت غير صحيح', 400);
            }

            // Cast vote
            $result = askro_cast_vote($post_id, $vote_type, $vote_value, $user_id);

            if (is_wp_error($result)) {
                return $this->error_response($result->get_error_message(), 400);
            }

            // Get updated vote counts
            $vote_counts = askro_get_post_votes($post_id);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'vote_counts' => $vote_counts,
                    'message' => 'تم التصويت بنجاح'
                ]
            ], 200);

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Search content endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function search_content($request) {
        try {
            $this->check_rate_limit('search');
            
            $query = sanitize_text_field($request->get_param('q'));
            $type = $request->get_param('type') ?: 'questions';
            $category = $request->get_param('category');
            $tag = $request->get_param('tag');
            $status = $request->get_param('status');

            if (empty($query)) {
                return $this->error_response('نص البحث مطلوب', 400);
            }

            $args = [
                's' => $query,
                'post_type' => $type === 'answers' ? 'askro_answer' : 'askro_question',
                'post_status' => 'publish',
                'posts_per_page' => $request->get_param('per_page') ?: 20,
                'paged' => $request->get_param('page') ?: 1
            ];

            // Add filters
            if ($category) {
                $args['tax_query'][] = [
                    'taxonomy' => 'askro_category',
                    'field' => 'slug',
                    'terms' => $category
                ];
            }

            if ($tag) {
                $args['tax_query'][] = [
                    'taxonomy' => 'askro_tag',
                    'field' => 'slug',
                    'terms' => $tag
                ];
            }

            if ($status) {
                $args['meta_query'][] = [
                    'key' => 'askro_status',
                    'value' => $status
                ];
            }

            $search_query = new WP_Query($args);
            $results = [];

            foreach ($search_query->posts as $post) {
                if ($type === 'answers') {
                    $results[] = $this->format_answer($post);
                } else {
                    $results[] = $this->format_question($post);
                }
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => $results,
                'pagination' => [
                    'current_page' => $args['paged'],
                    'total_pages' => $search_query->max_num_pages,
                    'total_items' => $search_query->found_posts,
                    'per_page' => $args['posts_per_page']
                ]
            ], 200);

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get leaderboard endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function get_leaderboard($request) {
        try {
            $timeframe = $request->get_param('timeframe') ?: 'all_time';
            $limit = $request->get_param('limit') ?: 10;

            global $wpdb;
            $table_name = $wpdb->prefix . 'askro_points_log';

            $where_clause = '';
            if ($timeframe === 'weekly') {
                $where_clause = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($timeframe === 'monthly') {
                $where_clause = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }

            $query = "
                SELECT user_id, SUM(points) as total_points
                FROM {$table_name}
                {$where_clause}
                GROUP BY user_id
                ORDER BY total_points DESC
                LIMIT {$limit}
            ";

            $results = $wpdb->get_results($query);
            $leaderboard = [];

            foreach ($results as $result) {
                $user_data = get_userdata($result->user_id);
                if ($user_data) {
                    $leaderboard[] = [
                        'user_id' => $result->user_id,
                        'display_name' => $user_data->display_name,
                        'avatar' => get_avatar_url($result->user_id),
                        'points' => $result->total_points,
                        'rank' => askro_get_user_rank($result->user_id)
                    ];
                }
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => $leaderboard
            ], 200);

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Format question data
     *
     * @param WP_Post $question
     * @param bool $include_answers
     * @return array
     * @since 1.0.0
     */
    private function format_question($question, $include_answers = false) {
        $author_data = askro_get_user_data($question->post_author);
        $categories = wp_get_post_terms($question->ID, 'askro_category');
        $tags = wp_get_post_terms($question->ID, 'askro_tag');
        $status = get_post_meta($question->ID, 'askro_status', true) ?: 'open';

        $formatted = [
            'id' => $question->ID,
            'title' => $question->post_title,
            'content' => $question->post_content,
            'excerpt' => wp_trim_words($question->post_content, 30),
            'status' => $status,
            'created_at' => $question->post_date,
            'updated_at' => $question->post_modified,
            'author' => [
                'id' => $question->post_author,
                'name' => $author_data['display_name'],
                'avatar' => $author_data['avatar'],
                'rank' => $author_data['rank']['current']['name']
            ],
            'stats' => [
                'views' => askro_get_post_views($question->ID),
                'answers' => askro_get_question_answers_count($question->ID),
                'votes' => askro_get_total_votes($question->ID)
            ],
            'categories' => array_map(function($cat) {
                return [
                    'id' => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug
                ];
            }, $categories),
            'tags' => array_map(function($tag) {
                return [
                    'id' => $tag->term_id,
                    'name' => $tag->name,
                    'slug' => $tag->slug
                ];
            }, $tags)
        ];

        if ($include_answers) {
            $answers = get_posts([
                'post_type' => 'askro_answer',
                'post_status' => 'publish',
                'meta_query' => [
                    [
                        'key' => 'askro_question_id',
                        'value' => $question->ID
                    ]
                ],
                'orderby' => 'meta_value_num',
                'meta_key' => 'askro_is_best_answer',
                'order' => 'DESC'
            ]);

            $formatted['answers'] = array_map([$this, 'format_answer'], $answers);
        }

        return $formatted;
    }

    /**
     * Format answer data
     *
     * @param WP_Post $answer
     * @return array
     * @since 1.0.0
     */
    private function format_answer($answer) {
        $author_data = askro_get_user_data($answer->post_author);
        $is_best = get_post_meta($answer->ID, 'askro_is_best_answer', true);
        $question_id = get_post_meta($answer->ID, 'askro_question_id', true);

        return [
            'id' => $answer->ID,
            'content' => $answer->post_content,
            'created_at' => $answer->post_date,
            'updated_at' => $answer->post_modified,
            'is_best_answer' => (bool) $is_best,
            'question_id' => $question_id,
            'author' => [
                'id' => $answer->post_author,
                'name' => $author_data['display_name'],
                'avatar' => $author_data['avatar'],
                'rank' => $author_data['rank']['current']['name']
            ],
            'stats' => [
                'votes' => askro_get_total_votes($answer->ID)
            ]
        ];
    }

    /**
     * Check rate limiting
     *
     * @param string $endpoint
     * @throws Exception
     * @since 1.0.0
     */
    private function check_rate_limit($endpoint) {
        if (!isset($this->rate_limits[$endpoint])) {
            return;
        }

        $user_id = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $key = "askro_rate_limit_{$endpoint}_{$user_id}_{$ip}";
        
        $limits = $this->rate_limits[$endpoint];
        $current = get_transient($key) ?: 0;

        if ($current >= $limits['requests']) {
            throw new Exception('تم تجاوز حد الطلبات المسموح. يرجى المحاولة لاحقاً.');
        }

        set_transient($key, $current + 1, $limits['window']);
    }

    /**
     * Add CORS headers
     *
     * @since 1.0.0
     */
    public function add_cors_headers() {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            }

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }

            exit(0);
        }
    }

    /**
     * Add rate limiting middleware
     *
     * @since 1.0.0
     */
    public function add_rate_limiting() {
        add_filter('rest_pre_dispatch', [$this, 'check_rate_limit_middleware'], 10, 3);
    }

    /**
     * Rate limiting middleware
     *
     * @param mixed $result
     * @param WP_REST_Server $server
     * @param WP_REST_Request $request
     * @return mixed
     * @since 1.0.0
     */
    public function check_rate_limit_middleware($result, $server, $request) {
        $route = $request->get_route();
        
        if (strpos($route, '/questions') !== false) {
            $this->check_rate_limit('questions');
        } elseif (strpos($route, '/answers') !== false) {
            $this->check_rate_limit('answers');
        } elseif (strpos($route, '/vote') !== false) {
            $this->check_rate_limit('votes');
        } elseif (strpos($route, '/search') !== false) {
            $this->check_rate_limit('search');
        }

        return $result;
    }

    /**
     * Permission callbacks
     *
     * @since 1.0.0
     */
    public function check_read_permission() {
        return true; // Public read access
    }

    public function check_create_permission() {
        return is_user_logged_in();
    }

    public function check_edit_permission($request) {
        $user_id = get_current_user_id();
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        return $user_id && $post && $post->post_author == $user_id;
    }

    public function check_delete_permission($request) {
        $user_id = get_current_user_id();
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        return $user_id && $post && ($post->post_author == $user_id || current_user_can('manage_options'));
    }

    public function check_vote_permission() {
        return is_user_logged_in();
    }

    public function check_authenticated() {
        return is_user_logged_in();
    }

    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Argument definitions
     *
     * @since 1.0.0
     */
    private function get_questions_args() {
        return [
            'per_page' => [
                'default' => 15,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 100;
                }
            ],
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint'
            ],
            'orderby' => [
                'default' => 'date',
                'enum' => ['date', 'title', 'views', 'answers', 'votes']
            ],
            'order' => [
                'default' => 'DESC',
                'enum' => ['ASC', 'DESC']
            ],
            'category' => [
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'tag' => [
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'status' => [
                'enum' => ['open', 'closed', 'solved']
            ]
        ];
    }

    private function get_create_question_args() {
        return [
            'title' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return !empty($param) && strlen($param) >= 10;
                }
            ],
            'content' => [
                'required' => true,
                'sanitize_callback' => 'wp_kses_post',
                'validate_callback' => function($param) {
                    return !empty($param) && strlen($param) >= 20;
                }
            ],
            'category' => [
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'tags' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ],
            'status' => [
                'default' => 'open',
                'enum' => ['open', 'closed', 'solved']
            ]
        ];
    }

    private function get_create_answer_args() {
        return [
            'content' => [
                'required' => true,
                'sanitize_callback' => 'wp_kses_post',
                'validate_callback' => function($param) {
                    return !empty($param) && strlen($param) >= 10;
                }
            ]
        ];
    }

    private function get_create_comment_args() {
        return [
            'content' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_textarea_field',
                'validate_callback' => function($param) {
                    return !empty($param) && strlen($param) >= 3 && strlen($param) <= 1000;
                }
            ]
        ];
    }

    private function get_vote_args() {
        return [
            'vote_type' => [
                'required' => true,
                'enum' => ['useful', 'innovative', 'well_researched', 'incorrect', 'redundant']
            ],
            'vote_value' => [
                'default' => 1,
                'type' => 'integer',
                'minimum' => -1,
                'maximum' => 1
            ]
        ];
    }

    private function get_search_args() {
        return [
            'q' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'type' => [
                'default' => 'questions',
                'enum' => ['questions', 'answers']
            ],
            'category' => [
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'tag' => [
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'status' => [
                'enum' => ['open', 'closed', 'solved']
            ],
            'per_page' => [
                'default' => 20,
                'sanitize_callback' => 'absint',
                'minimum' => 1,
                'maximum' => 100
            ],
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint'
            ]
        ];
    }

    private function get_leaderboard_args() {
        return [
            'timeframe' => [
                'default' => 'all_time',
                'enum' => ['all_time', 'weekly', 'monthly']
            ],
            'limit' => [
                'default' => 10,
                'sanitize_callback' => 'absint',
                'minimum' => 1,
                'maximum' => 100
            ]
        ];
    }

    /**
     * Error response helper
     *
     * @param string $message
     * @param int $status
     * @return WP_REST_Response
     * @since 1.0.0
     */
    private function error_response($message, $status = 400) {
        return new WP_REST_Response([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status
            ]
        ], $status);
    }

    /**
     * Add rewrite rules for custom API endpoints
     *
     * @since 1.0.0
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^' . $this->api_base . '/(.*)$',
            'index.php?askro_api=1&api_route=$matches[1]',
            'top'
        );
    }

    /**
     * Add query vars
     *
     * @param array $vars
     * @return array
     * @since 1.0.0
     */
    public function add_query_vars($vars) {
        $vars[] = 'askro_api';
        $vars[] = 'api_route';
        return $vars;
    }

    /**
     * Handle custom API requests
     *
     * @since 1.0.0
     */
    public function handle_api_requests() {
        if (get_query_var('askro_api')) {
            $route = get_query_var('api_route');
            $this->handle_custom_api_request($route);
        }
    }

    /**
     * Handle custom API request
     *
     * @param string $route
     * @since 1.0.0
     */
    private function handle_custom_api_request($route) {
        // Handle custom API routes if needed
        wp_die('API endpoint not found', 'Not Found', ['response' => 404]);
    }
} 
