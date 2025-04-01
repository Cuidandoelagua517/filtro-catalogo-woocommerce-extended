<?php
/**
 * Plugin Name: WooCommerce Template Customizer Extender
 * Plugin URI: https://tu-sitio.com/plugins/template-customizer-extender
 * Description: Plugin complementario para extender y personalizar la funcionalidad de WooCommerce Product Template Customizer
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tu-sitio.com
 * Text Domain: wc-template-extender
 * Requires PHP: 7.0
 * WC requires at least: 5.0.0
 * WC tested up to: 8.0.0
 * Requires at least: 5.0
 * Requires Plugins: woocommerce
 * 
 * This plugin is compatible with WooCommerce HPOS (Custom Order Tables).
 * COT: true
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del plugin complementario
 */
class WC_Template_Customizer_Extender {
    /**
     * Constructor
     */
    public function __construct() {
        // Verificar que el plugin principal esté activo
        add_action('plugins_loaded', array($this, 'check_parent_plugin'));
        
        // Declarar compatibilidad con HPOS para WooCommerce
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        
        // Inicializar el plugin si el principal está activo
        add_action('init', array($this, 'init'), 20);
    }

    /**
     * Declarar compatibilidad con HPOS (Custom Order Tables)
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            // Declarar compatibilidad con HPOS (Custom Order Tables)
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            
            // También declarar compatibilidad con bloques de carrito/checkout
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }

    /**
     * Verificar que el plugin principal esté activo
     */
    public function check_parent_plugin() {
        if (!class_exists('WC_Template_Customizer')) {
            add_action('admin_notices', array($this, 'parent_plugin_notice'));
            return false;
        }
        return true;
    }

    /**
     * Mostrar aviso si el plugin principal no está activo
     */
    public function parent_plugin_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('El plugin "WooCommerce Template Customizer Extender" requiere que "WooCommerce Product Template Customizer" esté instalado y activado.', 'wc-template-extender'); ?></p>
        </div>
        <?php
    }

    /**
     * Inicializar el plugin
     */
    public function init() {
        // Solo continuar si el plugin principal está activo
        if (!$this->check_parent_plugin()) {
            return;
        }

        // Cargar traducciones
        load_plugin_textdomain('wc-template-extender', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Incluir el archivo del widget personalizado
        require_once plugin_dir_path(__FILE__) . 'includes/class-custom-filter-widget.php';
        
        // Registrar el widget
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Aplicar filtros
        $this->apply_filters();
        
        // Registrar estilos
        add_action('wp_enqueue_scripts', array($this, 'register_styles'));
    }
    
    /**
     * Registrar widgets personalizados
     */
    public function register_widgets() {
        register_widget('WC_Extender_Custom_Filter_Widget');
    }
    
    /**
     * Registrar estilos CSS
     */
    public function register_styles() {
        // Solo en páginas de WooCommerce
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            return;
        }
        
        wp_register_style(
            'wc-template-extender-styles',
            plugin_dir_url(__FILE__) . 'assets/css/custom-styles.css',
            array('wc-template-customizer-styles'),
            '1.0.0'
        );
        
        wp_enqueue_style('wc-template-extender-styles');
    }

    /**
     * Aplicar filtros para personalizar el plugin principal
     */
    private function apply_filters() {
        // 1. Modificar los filtros disponibles
        add_filter('wc_template_customizer_available_filters', array($this, 'modify_available_filters'), 10, 1);
        
        // 2. Añadir clases personalizadas a los productos
        add_filter('wc_template_customizer_product_classes', array($this, 'add_custom_product_classes'), 10, 2);
        
        // 3. Modificar etiquetas (badges) de productos
        add_filter('wc_template_customizer_product_badges', array($this, 'modify_product_badges'), 10, 2);
        
        // 4. Añadir columna de ordenación personalizada
        add_filter('wc_template_customizer_orderby_options', array($this, 'add_custom_orderby_option'), 10, 1);
        
        // 5. Modificar el comportamiento del filtro de precios
        add_filter('wc_template_customizer_price_filter_args', array($this, 'modify_price_filter_args'), 10, 1);
        
        // 6. Añadir un filtro personalizado
        add_action('woocommerce_before_shop_loop', array($this, 'add_custom_filter'), 15);
        
        // 7. Personalizar colores predeterminados
        add_filter('wc_template_customizer_default_colors', array($this, 'modify_default_colors'), 10, 1);
        
        // 8. Añadir un gancho para procesar la selección de filtros personalizados
        add_action('pre_get_posts', array($this, 'process_custom_filter_selection'), 20);
    }
    
    /**
     * 1. Modificar los filtros disponibles
     */
    public function modify_available_filters($filters) {
        // Añadir un nuevo filtro personalizado
        $filters['custom_filter'] = array(
            'name' => __('Filtro Personalizado', 'wc-template-extender'),
            'description' => __('Filtro personalizado para necesidades específicas', 'wc-template-extender'),
            'widget' => 'WC_Custom_Filter_Widget'
        );
        
        // Modificar un filtro existente
        if (isset($filters['price'])) {
            $filters['price']['name'] = __('Rango de Precios', 'wc-template-extender');
            $filters['price']['description'] = __('Filtra productos por rango de precios personalizado', 'wc-template-extender');
        }
        
        // Desactivar un filtro si no lo necesitas
        // unset($filters['rating']);
        
        return $filters;
    }
    
    /**
     * 2. Añadir clases personalizadas a los productos
     */
    public function add_custom_product_classes($classes, $product) {
        // Añadir clase según el precio
        $price = $product->get_price();
        
        if ($price > 100) {
            $classes[] = 'producto-premium';
        } elseif ($price > 50) {
            $classes[] = 'producto-standard';
        } else {
            $classes[] = 'producto-basico';
        }
        
        // Añadir clase según la categoría
        $product_cats = wp_get_post_terms($product->get_id(), 'product_cat');
        if (!empty($product_cats)) {
            foreach ($product_cats as $cat) {
                $classes[] = 'cat-' . $cat->slug;
            }
        }
        
        return $classes;
    }
    
    /**
     * 3. Modificar etiquetas (badges) de productos
     */
    public function modify_product_badges($badges, $product) {
        // Añadir una nueva etiqueta para productos premium
        if ($product->get_price() > 100) {
            $badges[] = '<span class="badge badge-premium">' . __('Premium', 'wc-template-extender') . '</span>';
        }
        
        // Añadir una etiqueta para productos con envío gratuito
        $shipping_class_id = $product->get_shipping_class_id();
        if ($shipping_class_id) {
            $shipping_class = get_term($shipping_class_id, 'product_shipping_class');
            if ($shipping_class && $shipping_class->slug === 'free-shipping') {
                $badges[] = '<span class="badge badge-free-shipping">' . __('Envío Gratis', 'wc-template-extender') . '</span>';
            }
        }
        
        return $badges;
    }
    
    /**
     * 4. Añadir columna de ordenación personalizada
     */
    public function add_custom_orderby_option($orderby_options) {
        // Añadir opción para ordenar por disponibilidad (stock primero)
        $orderby_options['stock_status'] = __('Disponibilidad', 'wc-template-extender');
        
        return $orderby_options;
    }
    
    /**
     * 5. Modificar el comportamiento del filtro de precios
     */
    public function modify_price_filter_args($args) {
        // Cambiar el paso del slider de precios
        $args['step'] = 5; // De 5 en 5
        
        // Redondear los precios mínimo y máximo
        $args['min_price'] = floor($args['min_price'] / 10) * 10; // Redondear a la decena inferior
        $args['max_price'] = ceil($args['max_price'] / 10) * 10;  // Redondear a la decena superior
        
        return $args;
    }
    
    /**
     * 6. Añadir un filtro personalizado
     */
    public function add_custom_filter() {
        // Solo mostrar en la página de la tienda
        if (!is_shop() && !is_product_category() && !is_product_taxonomy()) {
            return;
        }
        
        // Obtener valor actual del filtro si existe
        $current_filter = isset($_GET['custom_filter']) ? sanitize_text_field($_GET['custom_filter']) : '';
        
        // Opciones de ejemplo para el filtro
        $options = array(
            '' => __('Todos los productos', 'wc-template-extender'),
            'popular' => __('Productos populares', 'wc-template-extender'),
            'nuevos' => __('Recién llegados', 'wc-template-extender'),
            'destacados' => __('Destacados', 'wc-template-extender')
        );
        
        ?>
        <div class="custom-filter-wrapper">
            <form method="get" action="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="woocommerce-custom-filter">
                <select name="custom_filter" class="custom-filter-select" onchange="this.form.submit()">
                    <?php foreach ($options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($current_filter, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php wc_query_string_form_fields(null, array('custom_filter', 'submit', 'paged', 'product-page')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * 7. Personalizar colores predeterminados
     */
    public function modify_default_colors($colors) {
        // Modificar los colores predeterminados
        $colors['primary_color'] = '#3498db';    // Azul
        $colors['secondary_color'] = '#2ecc71';  // Verde
        $colors['success_color'] = '#27ae60';    // Verde oscuro
        $colors['danger_color'] = '#e74c3c';     // Rojo
        
        return $colors;
    }
    
    /**
     * 8. Procesar la selección de filtros personalizados
     */
    public function process_custom_filter_selection($query) {
        // Solo procesar en la consulta principal de la tienda
        if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_category() || is_product_taxonomy())) {
            // Obtener el valor del filtro personalizado
            $custom_filter = isset($_GET['custom_filter']) ? sanitize_text_field($_GET['custom_filter']) : '';
            
            if (!empty($custom_filter)) {
                switch ($custom_filter) {
                    case 'popular':
                        // Ordenar por popularidad (más vendidos)
                        $query->set('meta_key', 'total_sales');
                        $query->set('orderby', 'meta_value_num');
                        $query->set('order', 'DESC');
                        break;
                        
                    case 'nuevos':
                        // Productos más recientes
                        $query->set('orderby', 'date');
                        $query->set('order', 'DESC');
                        break;
                        
                    case 'destacados':
                        // Solo productos destacados
                        $tax_query = (array) $query->get('tax_query');
                        $tax_query[] = array(
                            'taxonomy' => 'product_visibility',
                            'field'    => 'name',
                            'terms'    => 'featured',
                            'operator' => 'IN',
                        );
                        $query->set('tax_query', $tax_query);
                        break;
                }
            }
        }
    }
}

// Inicializar el plugin
new WC_Template_Customizer_Extender();
