<?php	


class my_custom_post_type {

	
	 
	public function get_tr_start( $atts = [] ) {

		$atts_str = '';
		if ( ! empty( $atts ) ) {
			$atts_str = ' ' . $this->get_custom_attributes( $atts );
		}
		return '<tr' . $atts_str . '>';
	}

	
	public function get_tr_end() {
		return '</tr>';
	}

	
	public function get_th_start( $atts = [] ) {
		$atts_str = '';
		if ( ! empty( $atts ) ) {
			$atts_str = ' ' . $this->get_custom_attributes( $atts );
		}
		return "<th scope=\"row\"{$atts_str}>";
	}
	
	public function get_th_end() {
		return '</th>';
	}

	
	public function get_td_start( $atts = [] ) {
		$atts_str = '';
		if ( ! empty( $atts ) ) {
			$atts_str = ' ' . $this->get_custom_attributes( $atts );
		}
		return "<td{$atts_str}>";
	}

	public function get_td_end() {
		return '</td>';
	}

	
	public function get_fieldset_start( $args = [], $atts = [] ) {
		$fieldset = '<fieldset';

		if ( ! empty( $args['id'] ) ) {
			$fieldset .= ' id="' . esc_attr( $args['id'] ) . '"';
		}

		if ( ! empty( $args['classes'] ) ) {
			$classes   = 'class="' . implode( ' ', $args['classes'] ) . '"';
			$fieldset .= ' ' . $classes;
		}

		if ( ! empty( $args['aria-expanded'] ) ) {
			$fieldset .= ' aria-expanded="' . $args['aria-expanded'] . '"';
		}

		if ( ! empty( $atts ) ) {
			$fieldset .= ' ' . $this->get_custom_attributes( $atts );
		}

		$fieldset .= ' tabindex="0">';

		return $fieldset;
	}

	
	public function get_fieldset_end() {
		return '</fieldset>';
	}

	
	public function get_legend_start( $atts = [] ) {
		$atts_str = '';
		if ( ! empty( $atts ) ) {
			$atts_str = ' ' . $this->get_custom_attributes( $atts );
		}
		return "<legend class=\"screen-reader-text\"{$atts_str}>";
	}

	
	public function get_legend_end() {
		return '</legend>';
	}

	
	public function get_p( $text = '' ) {
		return '<p>' . $text . '</p>';
	}

	
	public function get_label( $label_for = '', $label_text = '' ) {
		return '<label for="' . esc_attr( $label_for ) . '">' . wp_strip_all_tags( $label_text ) . '</label>';
	}

	
	public function get_required_attribute( $required = false ) {
		$attr = '';
		if ( $required ) {
			$attr .= 'required="true"';
		}
		return $attr;
	}

	public function get_required_span() {
		return ' <span class="required">*</span>';
	}

	
	public function get_aria_required( $required = false ) {
		$attr = $required ? 'true' : 'false';
		return 'aria-required="' . $attr . '"';
	}

	public function get_help( $help_text = '' ) {
		return '<a href="#" class="cptui-help dashicons-before dashicons-editor-help" title="' . esc_attr( $help_text ) . '"></a>';
	}
	
	public function get_description( $help_text = '' ) {
		return '<p class="cptui-field-description description">' . $help_text . '</p>';
	}


	public function get_maxlength( $length = '' ) {
		return 'maxlength="' . esc_attr( $length ) . '"';
	}

	
	public function get_onblur( $text = '' ) {
		return 'onblur="' . esc_attr( $text ) . '"';
	}

	
	public function get_placeholder( $text = '' ) {
		return 'placeholder="' . esc_attr( $text ) . '"';
	}

	
	public function get_hidden_text( $text = '' ) {
		return '<span class="visuallyhidden">' . $text . '</span>';
	}

	
	public function get_select_input( $args = [] ) {
		$defaults = $this->get_default_input_parameters(
			[
				'selections' => [],
			]
		);

		$args = wp_parse_args( $args, $defaults );

		$value = '';
		if ( $args['wrap'] ) {
			$value  = $this->get_tr_start();
			$value .= $this->get_th_start();
			$value .= $this->get_label( $args['name'], $args['labeltext'] );
			if ( $args['required'] ) {
				$value .= $this->get_required_span();
			}
			if ( ! empty( $args['helptext'] ) ) {
				$value .= $this->get_help( $args['helptext'] );
			}
			$value .= $this->get_th_end();
			$value .= $this->get_td_start();
		}

		$value .= '<select id="' . $args['name'] . '" name="' . $args['namearray'] . '[' . $args['name'] . ']">';
		if ( ! empty( $args['selections']['options'] ) && is_array( $args['selections']['options'] ) ) {
			foreach ( $args['selections']['options'] as $val ) {
				$result = '';
				$bool   = disp_boolean( $val['attr'] );

				if ( is_numeric( $args['selections']['selected'] ) ) {
					$selected = disp_boolean( $args['selections']['selected'] );
				} elseif ( in_array( $args['selections']['selected'], [ 'true', 'false' ], true ) ) {
					$selected = $args['selections']['selected'];
				}

				if ( ! empty( $selected ) && $selected === $bool ) {
					$result = ' selected="selected"';
				} else {
					if ( array_key_exists( 'default', $val ) && ! empty( $val['default'] ) ) {
						if ( empty( $selected ) ) {
							$result = ' selected="selected"';
						}
					}
				}

				if ( ! is_numeric( $args['selections']['selected'] ) && ( ! empty( $args['selections']['selected'] ) && $args['selections']['selected'] === $val['attr'] ) ) {
					$result = ' selected="selected"';
				}

				$value .= '<option value="' . $val['attr'] . '"' . $result . '>' . $val['text'] . '</option>';
			}
		}
		$value .= '</select>';

		if ( ! empty( $args['aftertext'] ) ) {
			$value .= ' ' . $this->get_description( $args['aftertext'] );
		}

		if ( $args['wrap'] ) {
			$value .= $this->get_td_end();
			$value .= $this->get_tr_end();
		}

		return $value;
	}

	
	public function get_text_input( $args = [] ) {
		$defaults = $this->get_default_input_parameters(
			[
				'maxlength' => '',
				'onblur'    => '',
			]
		);
		$args     = wp_parse_args( $args, $defaults );

		$value = '';
		if ( $args['wrap'] ) {
			$value .= $this->get_tr_start();
			$value .= $this->get_th_start();
			$value .= $this->get_label( $args['name'], $args['labeltext'] );
			if ( $args['required'] ) {
				$value .= $this->get_required_span();
			}
			$value .= $this->get_th_end();
			$value .= $this->get_td_start();
		}

		$value .= '<input type="text" id="' . $args['name'] . '" name="' . $args['namearray'] . '[' . $args['name'] . ']" value="' . $args['textvalue'] . '"';

		if ( $args['maxlength'] ) {
			$value .= ' ' . $this->get_maxlength( $args['maxlength'] );
		}

		if ( $args['onblur'] ) {
			$value .= ' ' . $this->get_onblur( $args['onblur'] );
		}

		$value .= ' ' . $this->get_aria_required( $args['required'] );

		$value .= ' ' . $this->get_required_attribute( $args['required'] );

		if ( ! empty( $args['aftertext'] ) ) {
			if ( $args['placeholder'] ) {
				$value .= ' ' . $this->get_placeholder( $args['aftertext'] );
			}
		}

		if ( ! empty( $args['data'] ) ) {
			foreach ( $args['data'] as $dkey => $dvalue ) {
				$value .= " data-{$dkey}=\"{$dvalue}\"";
			}
		}

		$value .= ' />';

		if ( ! empty( $args['aftertext'] ) ) {
			$value .= $this->get_hidden_text( $args['aftertext'] );
		}

		if ( $args['helptext'] ) {
			$value .= '<br/>' . $this->get_description( $args['helptext'] );
		}

		if ( $args['wrap'] ) {
			$value .= $this->get_td_end();
			$value .= $this->get_tr_end();
		}

		return $value;
	}

	
	public function get_textarea_input( $args = [] ) {
		$defaults = $this->get_default_input_parameters(
			[
				'rows' => '',
				'cols' => '',
			]
		);
		$args     = wp_parse_args( $args, $defaults );

		$value = '';

		if ( $args['wrap'] ) {
			$value .= $this->get_tr_start();
			$value .= $this->get_th_start();
			$value .= $this->get_label( $args['name'], $args['labeltext'] );
			if ( $args['required'] ) {
				$value .= $this->get_required_span();
			}
			$value .= $this->get_th_end();
			$value .= $this->get_td_start();
		}

		$value .= '<textarea id="' . $args['name'] . '" name="' . $args['namearray'] . '[' . $args['name'] . ']" rows="' . $args['rows'] . '" cols="' . $args['cols'] . '">' . $args['textvalue'] . '</textarea>';

		if ( ! empty( $args['aftertext'] ) ) {
			$value .= $args['aftertext'];
		}

		if ( $args['helptext'] ) {
			$value .= '<br/>' . $this->get_description( $args['helptext'] );
		}

		if ( $args['wrap'] ) {
			$value .= $this->get_td_end();
			$value .= $this->get_tr_end();
		}

		return $value;
	}

	
	public function get_check_input( $args = [] ) {
		$defaults = $this->get_default_input_parameters(
			[
				'checkvalue'    => '',
				'checked'       => 'true',
				'checklisttext' => '',
				'default'       => false,
			]
		);
		$args     = wp_parse_args( $args, $defaults );

		$value = '';
		if ( $args['wrap'] ) {
			$value .= $this->get_tr_start();
			$value .= $this->get_th_start();
			$value .= $args['checklisttext'];
			if ( $args['required'] ) {
				$value .= $this->get_required_span();
			}
			$value .= $this->get_th_end();
			$value .= $this->get_td_start();
		}

		if ( isset( $args['checked'] ) && 'false' === $args['checked'] ) {
			$value .= '<input type="checkbox" id="' . $args['name'] . '" name="' . $args['namearray'] . '[]" value="' . $args['checkvalue'] . '" />';
		} else {
			$value .= '<input type="checkbox" id="' . $args['name'] . '" name="' . $args['namearray'] . '[]" value="' . $args['checkvalue'] . '" checked="checked" />';
		}
		$value .= $this->get_label( $args['name'], $args['labeltext'] );
		$value .= '<br/>';

		if ( $args['wrap'] ) {
			$value .= $this->get_td_end();
			$value .= $this->get_tr_end();
		}

		return $value;
	}

	
	public function get_button( $args = [] ) {
		$value   = '';
		$classes = isset( $args['classes'] ) ? $args['classes'] : '';
		$value  .= '<input id="' . $args['id'] . '" class="button ' . $classes . '" type="button" value="' . $args['textvalue'] . '" />';

		return $value;
	}

	
	public function get_menu_icon_preview( $menu_icon = '' ) {
		$content = '';
		if ( ! empty( $menu_icon ) ) {
			$content = '<img src="' . $menu_icon . '">';
			if ( 0 === strpos( $menu_icon, 'dashicons-' ) ) {
				$content = '<div class="dashicons-before ' . $menu_icon . '"></div>';
			}
		}

		return '<div id="menu_icon_preview">' . $content . '</div>';
	}
public function get_default_input_parameters( $additions = [] ) {
		return array_merge(
			[
				'namearray'      => '',
				'name'           => '',
				'textvalue'      => '',
				'labeltext'      => '',
				'aftertext'      => '',
				'helptext'       => '',
				'helptext_after' => false,
				'required'       => false,
				'wrap'           => true,
				'placeholder'    => true,
			],
			(array) $additions
		);
	}
	public function get_custom_attributes( $attributes = [] ) {
		$formatted = [];
		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $key => $attribute ) {
				$formatted[] = "$key=\"$attribute\"";
			}
		}

		return implode( ' ', $formatted );
	}
}
