<?php
/**
 * Widget de Filtro Personalizado para WooCommerce
 * 
 * Este archivo puede incluirse directamente en el plugin complementario
 * o guardarse como un archivo separado e incluirse mediante require_once.
 */

if (!defined('ABSPATH')) {
    exit; // Salida si se accede directamente
}

/**
 * Widget personalizado para filtros especiales
 */
class WC_Extender_Custom_Filter_Widget extends WP_Widget {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'wc_extender_custom_filter',
            __('Filtro Especial', 'wc-template-extender'),
            array(
                'description' => __('Widget de filtro personalizado para productos específicos', 'wc-template-extender'),
                'customize_selective_refresh' => true,
            )
        );
    }

    /**
     * Front-end del widget
     */
    public function widget($args, $instance) {
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_product_taxonomy()) {
            return;
        }

        $title = apply_filters('widget_title', $instance['title'] ?? __('Filtro Especial', 'wc-template-extender'));
        $filter_type = $instance['filter_type'] ?? 'default';
        $show_count = !empty($instance['show_count']);
        $display_style = $instance['display_style'] ?? 'list';

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // Obtener valores actuales seleccionados
        $current_filter = isset($_GET['special_filter']) ? explode(',', sanitize_text_field($_GET['special_filter'])) : array();
        $current_filter = array_map('sanitize_title', $current_filter);

        // Renderizar el filtro según el tipo
        switch ($filter_type) {
            case 'marca':
                $this->render_brand_filter($current_filter, $show_count, $display_style);
                break;
            case 'popularidad':
                $this->render_popularity_filter($current_filter, $display_style);
                break;
            case 'descuento':
                $this->render_discount_filter($current_filter, $show_count, $display_style);
                break;
            default:
                $this->render_default_filter($current_filter, $show_count, $display_style);
                break;
        }

        echo $args['after_widget'];
    }

    /**
     * Formulario de administración del widget
     */
    public function form($instance) {
        $title = $instance['title'] ?? __('Filtro Especial', 'wc-template-extender');
        $filter_type = $instance['filter_type'] ?? 'default';
        $show_count = !empty($instance['show_count']);
        $display_style = $instance['display_style'] ?? 'list';

        // Opciones de tipo de filtro
        $filter_types = array(
            'default' => __('Filtro General', 'wc-template-extender'),
            'marca' => __('Marcas', 'wc-template-extender'),
            'popularidad' => __('Popularidad', 'wc-template-extender'),
            'descuento' => __('Descuentos', 'wc-template-extender'),
        );

        // Opciones de visualización
        $display_styles = array(
            'list' => __('Lista', 'wc-template-extender'),
            'buttons' => __('Botones', 'wc-template-extender'),
            'dropdown' => __('Desplegable', 'wc-template-extender'),
        );
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Título:', 'wc-template-extender'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('filter_type')); ?>"><?php esc_html_e('Tipo de filtro:', 'wc-template-extender'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('filter_type')); ?>" name="<?php echo esc_attr($this->get_field_name('filter_type')); ?>">
                <?php foreach ($filter_types as $key => $value) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($filter_type, $key); ?>><?php echo esc_html($value); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('display_style')); ?>"><?php esc_html_e('Estilo de visualización:', 'wc-template-extender'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('display_style')); ?>" name="<?php echo esc_attr($this->get_field_name('display_style')); ?>">
                <?php foreach ($display_styles as $key => $value) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($display_style, $key); ?>><?php echo esc_html($value); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <input type="checkbox" class="checkbox" id="<?php echo esc_attr($this->get_field_id('show_count')); ?>" name="<?php echo esc_attr($this->get_field_name('show_count')); ?>" <?php checked($show_count); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_count')); ?>"><?php esc_html_e('Mostrar conteo de productos', 'wc-template-extender'); ?></label>
        </p>
        <?php
    }

    /**
     * Procesar datos al guardar
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        $instance['filter_type'] = sanitize_key($new_instance['filter_type'] ?? 'default');
        $instance['show_count'] = !empty($new_instance['show_count']);
        $instance['display_style'] = sanitize_key($new_instance['display_style'] ?? 'list');
        
        return $instance;
    }

    /**
     * Renderiza filtro de marcas
     */
    private function render_brand_filter($current_filter, $show_count, $display_style) {
        // En este ejemplo, asumimos que las marcas se almacenan como un atributo de producto
        $attribute_name = 'pa_marca'; // Cambia esto según tu configuración
        
        // Obtener todas las marcas
        $terms = get_terms(array(
            'taxonomy' => $attribute_name,
            'hide_empty' => true,
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            echo '<p>' . esc_html__('No hay marcas disponibles.', 'wc-template-extender') . '</p>';
            return;
        }
        
        if ($display_style === 'dropdown') {
            $this->render_dropdown_filter($terms, $current_filter, $show_count, 'special_filter', 'marca');
        } elseif ($display_style === 'buttons') {
            $this->render_buttons_filter($terms, $current_filter, $show_count, 'special_filter', 'marca');
        } else {
            // Lista predeterminada
            echo '<ul class="special-filter-list brand-filter">';
            
            foreach ($terms as $term) {
                $count_html = $show_count ? ' <span class="count">(' . absint($term->count) . ')</span>' : '';
                $is_selected = in_array($term->slug, $current_filter);
                $term_class = $is_selected ? ' class="selected"' : '';
                
                // Crear enlace para alternar el filtro
                $filter_link = $this->get_filter_link($term->slug, $current_filter, 'special_filter');
                
                echo '<li' . $term_class . '>';
                echo '<a href="' . esc_url($filter_link) . '">';
                echo esc_html($term->name) . $count_html;
                echo '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
        }
    }
    
    /**
     * Renderiza filtro de popularidad
     */
    private function render_popularity_filter($current_filter, $display_style) {
        // Opciones de popularidad
        $popularity_options = array(
            'best_selling' => __('Más vendidos', 'wc-template-extender'),
            'top_rated' => __('Mejor valorados', 'wc-template-extender'),
            'featured' => __('Destacados', 'wc-template-extender'),
            'new_arrivals' => __('Recién llegados', 'wc-template-extender')
        );
        
        if ($display_style === 'dropdown') {
            echo '<form method="get" action="' . esc_url(wc_get_page_permalink('shop')) . '">';
            echo '<select name="special_filter" class="special-filter-select" onchange="this.form.submit()">';
            echo '<option value="">' . esc_html__('Seleccione una opción', 'wc-template-extender') . '</option>';
            
            foreach ($popularity_options as $value => $label) {
                $selected = in_array($value, $current_filter) ? ' selected="selected"' : '';
                echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
            }
            
            echo '</select>';
            wc_query_string_form_fields(null, array('special_filter', 'submit', 'paged'));
            echo '</form>';
        } elseif ($display_style === 'buttons') {
            echo '<div class="special-filter-buttons">';
            
            foreach ($popularity_options as $value => $label) {
                $is_selected = in_array($value, $current_filter);
                $class = $is_selected ? ' class="selected"' : '';
                $filter_link = $this->get_filter_link($value, $current_filter, 'special_filter');
                
                echo '<a href="' . esc_url($filter_link) . '"' . $class . '>' . esc_html($label) . '</a>';
            }
            
            echo '</div>';
        } else {
            // Lista predeterminada
            echo '<ul class="special-filter-list popularity-filter">';
            
            foreach ($popularity_options as $value => $label) {
                $is_selected = in_array($value, $current_filter);
                $term_class = $is_selected ? ' class="selected"' : '';
                $filter_link = $this->get_filter_link($value, $current_filter, 'special_filter');
                
                echo '<li' . $term_class . '>';
                echo '<a href="' . esc_url($filter_link) . '">';
                echo esc_html($label);
                echo '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
        }
    }
    
    /**
     * Renderiza filtro de descuentos
     */
    private function render_discount_filter($current_filter, $show_count, $display_style) {
        // Opciones de descuento
        $discount_options = array(
            'any_discount' => __('Cualquier descuento', 'wc-template-extender'),
            'discount_10' => __('10% o más', 'wc-template-extender'),
            'discount_25' => __('25% o más', 'wc-template-extender'),
            'discount_50' => __('50% o más', 'wc-template-extender')
        );
        
        // Obtener conteo de productos con descuento si es necesario
        $counts = array();
        if ($show_count) {
            $counts = $this->get_discount_counts();
        }
        
        if ($display_style === 'dropdown') {
            echo '<form method="get" action="' . esc_url(wc_get_page_permalink('shop')) . '">';
            echo '<select name="special_filter" class="special-filter-select" onchange="this.form.submit()">';
            echo '<option value="">' . esc_html__('Seleccione descuento', 'wc-template-extender') . '</option>';
            
            foreach ($discount_options as $value => $label) {
                $selected = in_array($value, $current_filter) ? ' selected="selected"' : '';
                $count_html = ($show_count && isset($counts[$value])) ? ' (' . absint($counts[$value]) . ')' : '';
                echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . $count_html . '</option>';
            }
            
            echo '</select>';
            wc_query_string_form_fields(null, array('special_filter', 'submit', 'paged'));
            echo '</form>';
        } elseif ($display_style === 'buttons') {
            echo '<div class="special-filter-buttons">';
            
            foreach ($discount_options as $value => $label) {
                $is_selected = in_array($value, $current_filter);
                $class = $is_selected ? ' class="selected"' : '';
                $filter_link = $this->get_filter_link($value, $current_filter, 'special_filter');
                $count_html = ($show_count && isset($counts[$value])) ? ' <span class="count">(' . absint($counts[$value]) . ')</span>' : '';
                
                echo '<a href="' . esc_url($filter_link) . '"' . $class . '>' . esc_html($label) . $count_html . '</a>';
            }
            
            echo '</div>';
        } else {
            // Lista predeterminada
            echo '<ul class="special-filter-list discount-filter">';
            
            foreach ($discount_options as $value => $label) {
                $is_selected = in_array($value, $current_filter);
                $term_class = $is_selected ? ' class="selected"' : '';
                $filter_link = $this->get_filter_link($value, $current_filter, 'special_filter');
                $count_html = ($show_count && isset($counts[$value])) ? ' <span class="count">(' . absint($counts[$value]) . ')</span>' : '';
                
                echo '<li' . $term_class . '>';
                echo '<a href="' . esc_url($filter_link) . '">';
                echo esc_html($label) . $count_html;
                echo '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
        }
    }
    
    /**
     * Renderiza filtro predeterminado
     */
    private function render_default_filter($current_filter, $show_count, $display_style) {
        // Opciones de filtro predeterminadas
        $default_options = array(
            'option1' => __('Opción 1', 'wc-template-extender'),
            'option2' => __('Opción 2', 'wc-template-extender'),
            'option3' => __('Opción 3', 'wc-template-extender'),
            'option4' => __('Opción 4', 'wc-template-extender')
        );
        
        if ($display_style === 'dropdown') {
            $this->render_dropdown_filter($default_options, $current_filter, $show_count, 'special_filter', 'default');
        } elseif ($display_style === 'buttons') {
            $this->render_buttons_filter($default_options, $current_filter, $show_count, 'special_filter', 'default');
        } else {
            // Lista predeterminada
            echo '<ul class="special-filter-list default-filter">';
            
            foreach ($default_options as $value => $label) {
                $is_selected = in_array($value, $current_filter);
                $term_class = $is_selected ? ' class="selected"' : '';
                $filter_link = $this->get_filter_link($value, $current_filter, 'special_filter');
                $count_html = $show_count ? ' <span class="count">(0)</span>' : '';
                
                echo '<li' . $term_class . '>';
                echo '<a href="' . esc_url($filter_link) . '">';
                echo esc_html($label) . $count_html;
                echo '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
        }
    }
    
    /**
     * Renderiza filtro como desplegable
     */
    private function render_dropdown_filter($options, $current_filter, $show_count, $filter_name, $filter_type) {
        echo '<form method="get" action="' . esc_url(wc_get_page_permalink('shop')) . '">';
        echo '<select name="' . esc_attr($filter_name) . '" class="special-filter-select" onchange="this.form.submit()">';
        echo '<option value="">' . esc_html__('Seleccione una opción', 'wc-template-extender') . '</option>';
        
        foreach ($options as $value => $label) {
            if (is_object($label) && isset($label->name)) {
                // Si es un objeto término
                $term = $label;
                $selected = in_array($term->slug, $current_filter) ? ' selected="selected"' : '';
                $count_html = $show_count ? ' (' . absint($term->count) . ')' : '';
                echo '<option value="' . esc_attr($term->slug) . '"' . $selected . '>' . esc_html($term->name) . $count_html . '</option>';
            } else {
                // Si es un array simple
                $selected = in_array($value, $current_filter) ? ' selected="selected"' : '';
                $count_html = ''; // Agregar conteo si es necesario
                echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . $count_html . '</option>';
            }
        }
        
        echo '</select>';
        wc_query_string_form_fields(null, array($filter_name, 'submit', 'paged'));
        echo '</form>';
    }
    
    /**
     * Renderiza filtro como botones
     */
    private function render_buttons_filter($options, $current_filter, $show_count, $filter_name, $filter_type) {
        echo '<div class="special-filter-buttons">';
        
        foreach ($options as $value => $label) {
            if (is_object($label) && isset($label->name)) {
                // Si es un objeto término
                $term = $label;
                $is_selected = in_array($term->slug, $current_filter);
                $class = $is_selected ? ' class="selected"' : '';
                $filter_link = $this->get_filter_link($term->slug, $current_filter, $filter_name);
                $count_html = $show_count ? ' <span class="count">(' . absint($term->count) . ')</span>' : '';
                
                echo '<a href="' . esc_url($filter_link) . '"' . $class . '>' . esc_html($term->name) . $count_html . '</a>';
            } else {
                // Si es un array simple
                $is_selected = in_array($value, $current_filter);
                $class = $is_selected ? ' class="selected"' : '';
                $filter_link = $this->get_filter_link($value, $current_filter, $filter_name);
                $count_html = ''; // Agregar conteo si es necesario
                
                echo '<a href="' . esc_url($filter_link) . '"' . $class . '>' . esc_html($label) . $count_html . '</a>';
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Obtiene conteo de productos con descuento
     */
    private function get_discount_counts() {
        $counts = array(
            'any_discount' => 0,
            'discount_10' => 0,
            'discount_25' => 0,
            'discount_50' => 0
        );
        
        // Esta es una implementación simplificada. En un escenario real,
        // necesitarías consultar la base de datos para obtener conteos precisos.
        
        // Ejemplo: obtener productos en oferta
        $args = array(
            'status' => 'publish',
            'limit' => -1,
            'return' => 'ids',
            'on_sale' => true,
        );
        
        $products = wc_get_products($args);
        
        if (!empty($products)) {
            $counts['any_discount'] = count($products);
            
            // Calcular conteos por porcentaje de descuento
            foreach ($products as $product_id) {
                $product = wc_get_product($product_id);
                if (!$product) continue;
                
                $regular_price = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
                
                if ($regular_price && $sale_price) {
                    $discount_percentage = 100 - (($sale_price / $regular_price) * 100);
                    
                    if ($discount_percentage >= 50) {
                        $counts['discount_50']++;
                    }
                    
                    if ($discount_percentage >= 25) {
                        $counts['discount_25']++;
                    }
                    
                    if ($discount_percentage >= 10) {
                        $counts['discount_10']++;
                    }
                }
            }
        }
        
        return $counts;
    }
    
    /**
     * Genera enlace para filtro
     */
    private function get_filter_link($value, $current_filter, $filter_name) {
        $base_link = remove_query_arg('paged');
        
        if (in_array($value, $current_filter)) {
            // Quitar el valor si ya está seleccionado
            $new_filter = array_diff($current_filter, array($value));
            
            if (empty($new_filter)) {
                $base_link = remove_query_arg($filter_name, $base_link);
            } else {
                $base_link = add_query_arg($filter_name, implode(',', $new_filter), $base_link);
            }
        } else {
            // Añadir el valor si no está seleccionado
            $new_filter = array_merge($current_filter, array($value));
            $base_link = add_query_arg($filter_name, implode(',', $new_filter), $base_link);
        }
        
        return $base_link;
    }
}
