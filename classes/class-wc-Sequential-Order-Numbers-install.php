<?php

class WC_Sequential_Order_Numbers_Install {

  public function __construct() {
    $this->install();
  }

	private function install() {
		
		$args = array(
				'posts_per_page' => -1,
				'post_type' => 'shop_order'
			);
		
		$all_order = get_posts( $args );
		
		if( !empty($all_order) && isset($all_order) ) {
			foreach( $all_order as $each_order ) {
				$order_id[] = $each_order->ID;
				update_option( 'ggggg', $each_order );
			}
			
			$max_order_id = max($order_id);
			
			update_option( 'woocommerce_order_number_start', $max_order_id+1 );
		} else

		    update_option( 'woocommerce_order_number_start', 1 );	
	}

} 
?>