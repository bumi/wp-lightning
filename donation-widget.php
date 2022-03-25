<?php
class DonationWidget extends WP_Widget
{
  function __construct($has_donated, $lnp_options)
  {
    $this->has_donated = $has_donated;
    $this->lnp_options = $lnp_options;

    parent::__construct(
      'lnp_donation_widget', // Base ID
      'Lightning Donation', // Name
      array('description' => __('A Lightning Domain Widget', 'text_domain'),) // Args
    );
  }

  public function widget($args, $instance)
  {
    extract($args);
    $title = apply_filters('widget_title', $instance['title']);
    $text = $instance['text'];
    $amount = $instance['amount'];
    $thank_you_message = $instance['thank_you_message'];
    $button = $instance['button'];
?>
    <?php echo $before_widget ?>
    <?php echo $before_title . $title . $after_title; ?>
    <div class="wp-lnp-all">
      <?php
      if ($this->has_donated) {
        echo '<p class="wp-all-confirmation">' . $thank_you_message . '</p>';
      } else {
      ?>
        <p class="wp-lnp-all-text"><?php echo str_replace(['%amount'], [$amount], $text) ?></p>
        <p><button class="wp-lnp-btn"><?php echo $button ?></button></p>
      <?php
      }
      ?>
    </div>
    <?php echo $after_widget ?>
  <?php
  }

  public function form($instance)
  {
    $title = empty($instance['title']) ? 'Donation' : $instance['title'];
    $text = empty($instance['text']) ? 'Donate %amount sats' : $instance['text'];
    $amount = empty($instance['amount']) ? 125 : $instance['amount'];
    $button = empty($instance['button']) ? 'Donate' : $instance['button'];
    $thank_you_message = empty($instance['thank_you_message']) ? 'Thank you for your donation!' : $instance['thank_you_message'];
  ?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('text'); ?>"><?php _e('Text:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>" type="text" value="<?php echo esc_attr($text); ?>" />
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('amount'); ?>"><?php _e('Amount:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('amount'); ?>" name="<?php echo $this->get_field_name('amount'); ?>" type="number" value="<?php echo esc_attr($amount); ?>" />
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('button'); ?>"><?php _e('Button:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('button'); ?>" name="<?php echo $this->get_field_name('button'); ?>" type="text" value="<?php echo esc_attr($button); ?>" />
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('thank_you_message'); ?>"><?php _e('Thank you message:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('thank_you_message'); ?>" name="<?php echo $this->get_field_name('thank_you_message'); ?>" type="text" value="<?php echo esc_attr($thank_you_message); ?>" />
    </p>
<?php
  }

  public function update($new_instance, $old_instance)
  {
    $instance = array();
    $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
    $instance['text'] = (!empty($new_instance['text'])) ? strip_tags($new_instance['text']) : '';
    $instance['button'] = (!empty($new_instance['button'])) ? strip_tags($new_instance['button']) : '';
    $instance['amount'] = (!empty($new_instance['amount'])) ? strip_tags($new_instance['amount']) : '';
    $instance['thank_you_message'] = (!empty($new_instance['thank_you_message'])) ? strip_tags($new_instance['thank_you_message']) : '';
    return $instance;
  }
}
?>