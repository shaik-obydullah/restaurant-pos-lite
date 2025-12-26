<?php
/**
 * Sales Management
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class Obydullah_Restaurant_POS_Lite_Sales
{
	public function __construct()
	{
		add_action('wp_ajax_orpl_get_sales', array($this, 'ajax_get_orpl_sales'));
		add_action('wp_ajax_orpl_delete_sale', array($this, 'ajax_delete_orpl_sale'));
		add_action('wp_ajax_orpl_print_sale', array($this, 'ajax_print_orpl_sale'));
	}

	/**
	 * Get sales table name
	 *
	 * @return string
	 */
	private function get_sales_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'orpl_sales';
	}

	/**
	 * Get customers table name
	 *
	 * @return string
	 */
	private function get_customers_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'orpl_customers';
	}

	/**
	 * Get sale details table name
	 *
	 * @return string
	 */
	private function get_sale_details_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'orpl_sale_details';
	}

	/**
	 * Get products table name
	 *
	 * @return string
	 */
	private function get_products_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'orpl_products';
	}

	/**
	 * Render the sales page
	 */
	public function render_page()
	{
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline mb-3">
				<?php esc_html_e('Sales Management', 'obydullah-restaurant-pos-lite'); ?>
			</h1>
			<hr class="wp-header-end">

			<div class="row">
				<div class="col-lg-12">
					<div class="bg-light p-4 rounded shadow-sm border">
						<h2 class="h5 mb-3 fw-semibold">
							<?php esc_html_e('Sales History', 'obydullah-restaurant-pos-lite'); ?>
						</h2>

						<!-- Search and Filter Section - Matches Stock page -->
						<div class="search-section mb-4 p-3 bg-white border rounded shadow-sm">
							<div class="d-flex flex-wrap align-items-center gap-2">
								<div class="search-group flex-grow-1">
									<label for="search-invoice" class="form-label mb-1">
										<?php esc_html_e('Search Invoice', 'obydullah-restaurant-pos-lite'); ?>
									</label>
									<div class="d-flex align-items-center gap-2">
										<div class="position-relative flex-grow-1">
											<input type="text" id="search-invoice"
												class="form-control form-control-sm"
												placeholder="<?php esc_attr_e('Invoice number...', 'obydullah-restaurant-pos-lite'); ?>">
											<button type="button" id="clear-invoice-search"
												class="btn btn-sm btn-link text-decoration-none position-absolute end-0 top-50 translate-middle-y"
												style="display: none; padding: 0;">
												<span class="text-muted fs-5">×</span>
											</button>
										</div>
									</div>
									<div class="form-text">
										<?php esc_html_e('Search by invoice number', 'obydullah-restaurant-pos-lite'); ?>
									</div>
								</div>
							</div>
						</div>

						<!-- Quick Filters Row - Fixed button alignment -->
						<div class="row g-2 mb-3">
							<div class="col-md-3">
								<label for="date-from" class="form-label small mb-1">
									<?php esc_html_e('Date From', 'obydullah-restaurant-pos-lite'); ?>
								</label>
								<input type="date" id="date-from" class="form-control form-control-sm">
							</div>
							<div class="col-md-3">
								<label for="date-to" class="form-label small mb-1">
									<?php esc_html_e('Date To', 'obydullah-restaurant-pos-lite'); ?>
								</label>
								<input type="date" id="date-to" class="form-control form-control-sm">
							</div>
							<div class="col-md-3">
								<label for="sale-type" class="form-label small mb-1">
									<?php esc_html_e('Sale Type', 'obydullah-restaurant-pos-lite'); ?>
								</label>
								<select id="sale-type" class="form-control form-control-sm">
									<option value=""><?php esc_html_e('All Types', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="dineIn"><?php esc_html_e('Dine In', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="takeAway"><?php esc_html_e('Take Away', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="pickUp"><?php esc_html_e('Pick Up', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="delivery"><?php esc_html_e('Delivery', 'obydullah-restaurant-pos-lite'); ?></option>
								</select>
							</div>
							<div class="col-md-3">
								<label for="sale-status" class="form-label small mb-1">
									<?php esc_html_e('Sale Status', 'obydullah-restaurant-pos-lite'); ?>
								</label>
								<select id="sale-status" class="form-control form-control-sm">
									<option value=""><?php esc_html_e('All Status', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="completed"><?php esc_html_e('Completed', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="saveSale"><?php esc_html_e('Saved', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="pending"><?php esc_html_e('Pending', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="canceled"><?php esc_html_e('Canceled', 'obydullah-restaurant-pos-lite'); ?></option>
								</select>
							</div>
						</div>

						<!-- Action Buttons Row - Matches Stock page -->
						<div class="row g-2 mb-3">
							<div class="col-md-12">
								<div class="d-flex align-items-center gap-2">
									<button type="button" id="search-sales" class="btn btn-primary btn-sm">
										<span class="btn-text"><?php esc_html_e('Search', 'obydullah-restaurant-pos-lite'); ?></span>
										<span class="spinner" style="display: none; margin-left: 5px;"></span>
									</button>
									<button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm ml-1">
										<?php esc_html_e('Reset', 'obydullah-restaurant-pos-lite'); ?>
									</button>
								</div>
							</div>
						</div>

						<!-- Sales Table - Matches Stock page structure -->
						<div class="table-responsive">
							<table class="table table-striped table-hover table-bordered mb-2">
								<thead>
									<tr class="bg-primary text-white">
										<th width="120"><?php esc_html_e('Invoice ID', 'obydullah-restaurant-pos-lite'); ?></th>
										<th width="100"><?php esc_html_e('Date', 'obydullah-restaurant-pos-lite'); ?></th>
										<th><?php esc_html_e('Customer', 'obydullah-restaurant-pos-lite'); ?></th>
										<th width="100"><?php esc_html_e('Type', 'obydullah-restaurant-pos-lite'); ?></th>
										<th width="100"><?php esc_html_e('Net Price', 'obydullah-restaurant-pos-lite'); ?></th>
										<th width="80"><?php esc_html_e('Tax', 'obydullah-restaurant-pos-lite'); ?></th>
										<th width="80"><?php esc_html_e('Vat', 'obydullah-restaurant-pos-lite'); ?></th>
										<th width="100"><?php esc_html_e('Discount', 'obydullah-restaurant-pos-lite'); ?></th>
										<th width="120"><?php esc_html_e('Grand Total', 'obydullah-restaurant-pos-lite'); ?></th>
										<th width="100"><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
										<th width="150" class="text-right"><?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?></th>
									</tr>
								</thead>
								<tbody id="sales-list" class="bg-white">
									<tr>
										<td colspan="11" class="text-center p-4">
											<span class="spinner is-active"></span>
											<?php esc_html_e('Loading sales...', 'obydullah-restaurant-pos-lite'); ?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>

						<!-- Pagination - Exact same as Stock page -->
						<div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
							<div class="tablenav-pages">
								<span class="displaying-num" id="displaying-num">0 <?php esc_html_e('items', 'obydullah-restaurant-pos-lite'); ?></span>
								<span class="pagination-links ms-2">
									<a class="first-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('First page', 'obydullah-restaurant-pos-lite'); ?>">«</a>
									<a class="prev-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('Previous page', 'obydullah-restaurant-pos-lite'); ?>">‹</a>
									<span class="paging-input">
										<input class="current-page form-control form-control-sm" id="current-page-selector" type="text" name="paged" value="1">
										<span class="tablenav-paging-text"><?php esc_html_e('of', 'obydullah-restaurant-pos-lite'); ?> <span class="total-pages">1</span></span>
									</span>
									<a class="next-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('Next page', 'obydullah-restaurant-pos-lite'); ?>">›</a>
									<a class="last-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('Last page', 'obydullah-restaurant-pos-lite'); ?>">»</a>
								</span>
							</div>
							<div class="tablenav-pages">
								<select id="per-page-select" class="form-control form-control-sm">
									<option value="10">10 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="20">20 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="50">50 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
									<option value="100">100 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/** Get sales with pagination and search */
	public function ajax_get_orpl_sales()
	{
		// Check nonce
		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'] ?? '')), 'orpl_get_sales')) {
			wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
		}

		// Check capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-pos-lite'));
		}

		global $wpdb;
		$sales_table = $this->get_sales_table_name();
		$customers_table = $this->get_customers_table_name();

		$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
		$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
		$search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
		$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
		$date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
		$sale_type = isset($_GET['sale_type']) ? sanitize_text_field(wp_unslash($_GET['sale_type'])) : '';
		$status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';

		$offset = ($page - 1) * $per_page;

		// Build WHERE clause
		$where_clause = '1=1';
		$prepare_args = array();

		if (!empty($search)) {
			$where_clause .= ' AND s.invoice_id LIKE %s';
			$prepare_args[] = '%' . $wpdb->esc_like($search) . '%';
		}

		if (!empty($date_from)) {
			$where_clause .= ' AND DATE(s.created_at) >= %s';
			$prepare_args[] = $date_from;
		}

		if (!empty($date_to)) {
			$where_clause .= ' AND DATE(s.created_at) <= %s';
			$prepare_args[] = $date_to;
		}

		if (!empty($sale_type)) {
			$where_clause .= ' AND s.sale_type = %s';
			$prepare_args[] = $sale_type;
		}

		if (!empty($status)) {
			$where_clause .= ' AND s.status = %s';
			$prepare_args[] = $status;
		}

		// Get total count
		$count_query = "SELECT COUNT(*) FROM {$sales_table} s WHERE $where_clause";
		if (!empty($prepare_args)) {
			$count_query = $wpdb->prepare($count_query, $prepare_args);
		}
		$total = $wpdb->get_var($count_query);

		// Get sales data with customer information
		$query = "
        SELECT s.*, c.name as customer_name 
        FROM {$sales_table} s 
        LEFT JOIN {$customers_table} c ON s.fk_customer_id = c.id 
        WHERE {$where_clause} 
        ORDER BY s.created_at DESC 
        LIMIT %d OFFSET %d
    ";

		// Always add pagination parameters
		$pagination_args = array($per_page, $offset);

		if (!empty($prepare_args)) {
			$query = $wpdb->prepare($query, array_merge($prepare_args, $pagination_args));
		} else {
			$query = $wpdb->prepare($query, $pagination_args);
		}

		$sales = $wpdb->get_results($query);

		// Calculate showing range
		$showing_from = $total > 0 ? $offset + 1 : 0;
		$showing_to = min($offset + $per_page, $total);

		wp_send_json_success(
			array(
				'sales' => $sales,
				'total' => $total,
				'showing_from' => $showing_from,
				'showing_to' => $showing_to,
				'current_page' => $page,
				'per_page' => $per_page,
			)
		);
	}

	/** Print sale (get sale details for printing) */
	public function ajax_print_orpl_sale()
	{
		// Check nonce
		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'orpl_print_sale')) {
			wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
		}

		global $wpdb;
		$sales_table = $this->get_sales_table_name();
		$sale_details_table = $this->get_sale_details_table_name();
		$products_table = $this->get_products_table_name();
		$customers_table = $this->get_customers_table_name();

		$sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;

		if (empty($sale_id)) {
			wp_send_json_error(__('Invalid sale ID', 'obydullah-restaurant-pos-lite'));
		}

		// Get sale details with customer info using sale ID
		$sale = $wpdb->get_row(
			$wpdb->prepare(
				"
					SELECT s.*, c.name as customer_name, c.mobile as customer_mobile, c.email as customer_email, c.address as customer_address
					FROM {$sales_table} s 
					LEFT JOIN {$customers_table} c ON s.fk_customer_id = c.id 
					WHERE s.id = %d
				",
				$sale_id
			)
		);

		if (!$sale) {
			wp_send_json_error(__('Sale not found', 'obydullah-restaurant-pos-lite'));
		}

		// Get sale details with product names
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"
					SELECT sd.*, p.name as product_name 
					FROM {$sale_details_table} sd 
					LEFT JOIN {$products_table} p ON sd.fk_product_id = p.id 
					WHERE sd.fk_sale_id = %d
					ORDER BY sd.id ASC
				",
				$sale->id
			)
		);

		$sale->items = $items;
		$sale->shop_info = Obydullah_Restaurant_POS_Lite_Helpers::get_shop_info();
		
		wp_send_json_success($sale);
	}

	/** Delete sale */
	public function ajax_delete_orpl_sale()
	{
		// Check nonce
		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'orpl_delete_sale')) {
			wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
		}

		global $wpdb;
		$sales_table = $this->get_sales_table_name();
		$sale_details_table = $this->get_sale_details_table_name();

		$id = intval($_POST['id'] ?? 0);

		if (!$id) {
			wp_send_json_error(__('Invalid sale ID', 'obydullah-restaurant-pos-lite'));
		}

		// Start transaction
		$wpdb->query('START TRANSACTION');

		try {
			// Delete sale details first
			$items_deleted = $wpdb->delete($sale_details_table, array('fk_sale_id' => $id), array('%d'));

			if (false === $items_deleted) {
				throw new Exception(__('Failed to delete sale details', 'obydullah-restaurant-pos-lite'));
			}

			// Delete sale
			$sale_deleted = $wpdb->delete($sales_table, array('id' => $id), array('%d'));

			if (false === $sale_deleted) {
				throw new Exception(__('Failed to delete sale', 'obydullah-restaurant-pos-lite'));
			}

			$wpdb->query('COMMIT');
			wp_send_json_success(__('Sale deleted successfully', 'obydullah-restaurant-pos-lite'));

		} catch (Exception $e) {
			$wpdb->query('ROLLBACK');
			wp_send_json_error($e->getMessage());
		}
	}
}