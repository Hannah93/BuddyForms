<?php

function buddyforms_form_html( $args )
{
    global $buddyforms;

    extract(shortcode_atts(array(
        'post_type' => '',
        'the_post' => 0,
        'customfields' => false,
        'post_id' => false,
        'revision_id' => false,
        'post_parent' => 0,
        'redirect_to' => esc_url($_SERVER['REQUEST_URI']),
        'form_slug' => '',
        'form_notice' => ''
    ), $args));

    session_id('buddyforms-create-edit-form');

    $form_html = '<div class="the_buddyforms_form the_buddyforms_form_' . $form_slug . '"">';


    $form_html .= '
    <script>
    jQuery(function() {
        var validator_' . $form_slug . ' = jQuery("#editpost_' . $form_slug . '").submit(function() {
                // update underlying textarea before submit validation
                tinyMCE.triggerSave();
        }).validate({
        ignore: "",
        rules: {
        ';

    foreach ($buddyforms['buddyforms'][$form_slug]['form_fields'] as $key => $form_field) {
        if (isset($form_field['required']) || $form_field['slug'] == 'editpost_title') {

            $field_slug = str_replace("-", "", $form_field['slug']);
            if ($field_slug) :
                $form_html .= $field_slug . ': {
                        required: true,';

                if (isset($form_field['validation_min']) && $form_field['validation_min'] > 0)
                    $form_html .= 'min: ' . $form_field['validation_min'] . ',';

                if (isset($form_field['validation_max']) && $form_field['validation_max'] > 0)
                    $form_html .= 'max: ' . $form_field['validation_max'] . ',';

                if (isset($form_field['validation_minlength']) && $form_field['validation_minlength'] > 0)
                    $form_html .= 'minlength: ' . $form_field['validation_minlength'] . ',';

                if (isset($form_field['validation_maxlength']) && $form_field['validation_maxlength'] > 0)
                    $form_html .= 'maxlength: ' . $form_field['validation_maxlength'] . ',';


                $form_html .= '},';
            endif;
        }
    }

    $form_html .= '},
        messages: {
            ';
    foreach($buddyforms['buddyforms'][$form_slug]['form_fields'] as $key =>  $form_field ){
        if(isset($form_field['required']) || $form_field['slug'] == 'editpost_title'){

            $validation_error_message = __('This field is required.', 'buddyforms');
            if(isset($form_field['validation_error_message']))
                $validation_error_message = $form_field['validation_error_message'];

                $field_slug = str_replace("-", "", $form_field['slug']);
                if($field_slug) :
                    $form_html .= $field_slug . ': {
                        required: "' . $validation_error_message . '",
                    },';
                endif;
        }
    }
    $form_html .= '},';

    $form_html .= 'errorPlacement: function(label, element) {
            // position error label after generated textarea
            if (element.is("textarea")) {
                jQuery("#editpost_title").prev().css(\'color\',\'red\');
                label.insertBefore("#editpost_content");
            } else {
                label.insertAfter(element)
            }
        }
    });
    validator_' . $form_slug . '.focusInvalid = function() {
        // put focus on tinymce on submit validation
        if (this.settings.focusInvalid) {
            try {
                var toFocus = jQuery(this.findLastActive() || this.errorList.length && this.errorList[0].element || []);
                if (toFocus.is("textarea")) {
                    tinyMCE.get(toFocus.attr("id")).focus();
                } else {
                    toFocus.filter(":visible").focus();
                }
            } catch (e) {
                // ignore IE throwing errors when focusing hidden elements
            }
        }
    }
    });
    </script>';

    if ( !is_user_logged_in() ) :
        $wp_login_form = '<h3>' . __('You need to be logged in to use this Form', 'buddyforms') . '</h3>';
        $wp_login_form .= apply_filters( 'buddyforms_wp_login_form', wp_login_form(array('echo' => false)) );
        return $wp_login_form;
    endif;

    $user_can_edit = false;
    if( empty($post_id) && current_user_can('buddyforms_' . $form_slug . '_create')) {
        $user_can_edit = true;
    } elseif( !empty($post_id) && current_user_can('buddyforms_' . $form_slug . '_edit')){
        $user_can_edit = true;
    }

    $user_can_edit = apply_filters( 'buddyforms_user_can_edit', $user_can_edit );

    if ( $user_can_edit == false ){
        $error_message = __('You do not have the required user role to use this form', 'buddyforms');
        return '<div class="error alert">'.$error_message.'</div>'; //das sieht nicht sauber aus
    }

    $form_html .= '<div id="form_message_' . $form_slug. '">'.$form_notice.'</div>';

    $form_html .= '<div class="form_wrapper">';

    // Create the form object
    $form = new Form("editpost_".$form_slug);

    // Set the form attribute
    $form->configure(array(
        "prevent" => array("bootstrap", "jQuery", "focus"),
        "action" => $redirect_to,
        "view" => new View_Vertical,
        'class' => 'standard-form',
    ));

    $form->addElement(new Element_HTML(do_action('template_notices')));
    $form->addElement(new Element_HTML(wp_nonce_field('buddyforms_form_nonce', '_wpnonce', true, false)));

    $form->addElement(new Element_Hidden("redirect_to"  , $redirect_to));

    $form->addElement(new Element_Hidden("post_id"      , $post_id));
    $form->addElement(new Element_Hidden("revision_id"  , $revision_id));
    $form->addElement(new Element_Hidden("post_parent"  , $post_parent));
    $form->addElement(new Element_Hidden("form_slug"    , $form_slug));
    $form->addElement(new Element_Hidden("bf_post_type"    , $post_type));

    if(!isset($buddyforms['buddyforms'][$form_slug]['bf_ajax']))
        $form->addElement(new Element_Hidden("ajax"     , 'off'));

    // if the form have custom field to save as post meta data they get displayed here
    bf_form_elements($form, $args);

    $form->addElement(new Element_Hidden("submitted", 'true', array('value' => 'true', 'id' => "submitted")));

    $form_button = apply_filters('buddyforms_create_edit_form_button',new Element_Button(__('Submit', 'buddyforms'), 'submit', array( 'id'=> $form_slug, 'class' => 'bf-submit', 'name' => 'submitted')));

    if($form_button)
        $form->addElement($form_button);

    $form = apply_filters( 'bf_form_before_render', $form, $args);

    // thats it! render the form!
    ob_start();
    $form->render();
    $form_html .= ob_get_contents();
    ob_clean();

    $form_html .= '<div class="bf_modal"></div></div>';

    if (isset($buddyforms['buddyforms'][$form_slug]['revision']) && $post_id != 0) {
        ob_start();
        buddyforms_wp_list_post_revisions($post_id);
        $form_html .= ob_get_contents();
        ob_clean();
    }
    $form_html .= '</div>';

    return $form_html;
}