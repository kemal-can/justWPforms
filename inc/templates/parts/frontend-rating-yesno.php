<?php $rating_labels = $part['rating_labels_yesno']; ?>

<input class="justwpforms-visuallyhidden" type="radio" value="0" id="<?php echo esc_attr( $part['id'] ); ?>_0" name="<?php justwpforms_the_part_name( $part, $form ); ?>" checked <?php justwpforms_the_part_attributes( $part, $form ); ?>>

<input class="justwpforms-visuallyhidden" type="radio" value="1" id="<?php echo esc_attr( $part['id'] ); ?>_1" name="<?php justwpforms_the_part_name( $part, $form ); ?>" <?php checked( justwpforms_get_part_value( $part, $form ), 1 ); ?> <?php justwpforms_the_part_attributes( $part, $form ); ?>>
<label class="justwpforms-rating__label" for="<?php echo esc_attr( $part['id'] ); ?>_1">
    <span class="justwpforms-rating__item-wrap">
        <?php echo $icons[0]; ?>
        <span class="justwpforms-rating__item-label"><?php echo ( ! empty( $rating_labels[0] ) ) ? $rating_labels[0] : '<span class="justwpforms-visuallyhidden">'. __( 'No', 'justwpforms' ) .'</span>'; ?></span>
    </span>
</label>

<input class="justwpforms-visuallyhidden" type="radio" value="2" id="<?php echo esc_attr( $part['id'] ); ?>_2" name="<?php justwpforms_the_part_name( $part, $form ); ?>" <?php checked( justwpforms_get_part_value( $part, $form ), 2 ); ?> <?php justwpforms_the_part_attributes( $part, $form ); ?> />
<label class="justwpforms-rating__label" for="<?php echo esc_attr( $part['id'] ); ?>_2">
    <span class="justwpforms-rating__item-wrap">
        <?php echo $icons[1]; ?>
        <span class="justwpforms-rating__item-label"><?php echo ( ! empty( $rating_labels[1] ) ) ? $rating_labels[1] : '<span class="justwpforms-visuallyhidden">'. __( 'Yes', 'justwpforms' ) .'</span>'; ?></span>
    </span>
</label>
