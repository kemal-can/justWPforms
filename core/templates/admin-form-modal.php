<div id="justwpforms-modal">
    <div>
        <select class="justwpforms-dialog__select" id="justwpforms-dialog-select">
            <?php $forms = $this->get_form_data_array(); ?>
            <option value=""><?php _e( 'Choose a form', 'justwpforms' ); ?></option>
            <?php foreach ( $forms as $form ) : ?>
            <option value="<?php echo $form['id']; ?>"><?php echo $form['title']; ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <button class="button-primary justwpforms-dialog__button"><?php _e( 'Insert', 'justwpforms' ); ?></button>
</div>
