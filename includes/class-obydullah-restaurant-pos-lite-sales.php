<?php
/**
 * Sales Management
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Sales class for Restaurant POS Lite
 */
class Obydullah_Restaurant_POS_Lite_Sales
{

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		add_action('wp_ajax_orpl_get_sales', array($this, 'ajax_get_orpl_sales'));
		add_action('wp_ajax_orpl_delete_sale', array($this, 'ajax_delete_orpl_sale'));
		add_action('wp_ajax_orpl_print_sale', array($this, 'ajax_print_orpl_sale'));
	}

	/**
	 * Format currency using helper class
	 *
	 * @param float $amount The amount to format.
	 * @return string
	 */
	private function format_currency($amount)
	{
		return Obydullah_Restaurant_POS_Lite_Helpers::format_currency($amount);
	}

	/**
	 * Format date using helper class
	 *
	 * @param string $date_string The date string to format.
	 * @return string
	 */
	private function format_date($date_string)
	{
		return Obydullah_Restaurant_POS_Lite_Helpers::format_date($date_string);
	}

	/**
	 * Get shop name using helper class
	 *
	 * @return string
	 */
	private function get_shop_name()
	{
		return Obydullah_Restaurant_POS_Lite_Helpers::get_shop_name();
	}

	/**
	 * Get shop info using helper class
	 *
	 * @return array
	 */
	private function get_shop_info()
	{
		return Obydullah_Restaurant_POS_Lite_Helpers::get_shop_info();
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
		$shop_name = $this->get_shop_name();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline" style="margin-bottom:20px;">
				<?php echo esc_html($shop_name); ?> - <?php esc_html_e('Sales', 'obydullah-restaurant-pos-lite'); ?>
			</h1>
			<hr class="wp-header-end">

			<!-- Search and Filter Section -->
			<div class="sales-header" style="margin-bottom:20px;">
				<div class="sales-filters" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
					<!-- Search by Invoice ID -->
					<div class="search-box">
						<input type="text" id="search-invoice"
							placeholder="<?php esc_attr_e('Search by Invoice ID...', 'obydullah-restaurant-pos-lite'); ?>"
							style="padding:6px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;min-width:250px;">
					</div>

					<!-- Date Range Filter -->
					<div class="date-filters" style="display:flex;gap:8px;align-items:center;">
						<input type="date" id="date-from"
							style="padding:6px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;">
						<span><?php esc_html_e('to', 'obydullah-restaurant-pos-lite'); ?></span>
						<input type="date" id="date-to"
							style="padding:6px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;">
					</div>

					<!-- Sale Type Filter -->
					<div class="type-filter">
						<select id="sale-type"
							style="padding:6px 25px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;">
							<option value=""><?php esc_html_e('All Types', 'obydullah-restaurant-pos-lite'); ?></option>
							<option value="dineIn"><?php esc_html_e('Dine In', 'obydullah-restaurant-pos-lite'); ?></option>
							<option value="takeAway"><?php esc_html_e('Take Away', 'obydullah-restaurant-pos-lite'); ?>
							</option>
							<option value="pickUp"><?php esc_html_e('Pick Up', 'obydullah-restaurant-pos-lite'); ?></option>
						</select>
					</div>

					<!-- Status Filter -->
					<div class="status-filter">
						<select id="sale-status"
							style="padding:6px 25px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;">
							<option value=""><?php esc_html_e('All Status', 'obydullah-restaurant-pos-lite'); ?></option>
							<option value="completed"><?php esc_html_e('Completed', 'obydullah-restaurant-pos-lite'); ?>
							</option>
							<option value="saveSale"><?php esc_html_e('Saved', 'obydullah-restaurant-pos-lite'); ?></option>
							<option value="canceled"><?php esc_html_e('Canceled', 'obydullah-restaurant-pos-lite'); ?>
							</option>
						</select>
					</div>

					<!-- Action Buttons -->
					<button type="button" id="search-sales" class="button button-primary" style="padding:6px 12px;">
						<?php esc_html_e('Search', 'obydullah-restaurant-pos-lite'); ?>
					</button>
					<button type="button" id="reset-filters" class="button" style="padding:6px 12px;">
						<?php esc_html_e('Reset', 'obydullah-restaurant-pos-lite'); ?>
					</button>
				</div>
			</div>

			<!-- Sales Table -->
			<div class="sales-table-wrapper">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e('Invoice ID', 'obydullah-restaurant-pos-lite'); ?></th>
							<th><?php esc_html_e('Date', 'obydullah-restaurant-pos-lite'); ?></th>
							<th><?php esc_html_e('Customer', 'obydullah-restaurant-pos-lite'); ?></th>
							<th><?php esc_html_e('Type', 'obydullah-restaurant-pos-lite'); ?></th>
							<th><?php esc_html_e('Net Price', 'obydullah-restaurant-pos-lite'); ?></th>
							<th><?php esc_html_e('Tax', 'obydullah-restaurant-pos-lite'); ?></th>
							<th><?php esc_html_e('Vat', 'obydullah-restaurant-pos-lite'); ?></th>
							<th><?php esc_html_e('Discount', 'obydullah-restaurant-pos-lite'); ?></th>
							<th><?php esc_html_e('Grand Total', 'obydullah-restaurant-pos-lite'); ?></th>
							<th><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
							<th style="text-align:center;"><?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?>
							</th>
						</tr>
					</thead>
					<tbody id="sales-list">
						<tr>
							<td colspan="11" class="loading-sales" style="text-align:center;">
								<span class="spinner is-active"></span>
								<?php esc_html_e('Loading sales...', 'obydullah-restaurant-pos-lite'); ?>
							</td>
						</tr>
					</tbody>
				</table>

				<!-- Products-style Pagination -->
				<div class="tablenav bottom"
					style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;">
					<div class="tablenav-pages">
						<span class="displaying-num" id="displaying-num">0
							<?php esc_html_e('items', 'obydullah-restaurant-pos-lite'); ?></span>
						<span class="pagination-links">
							<a class="first-page button" href="#">
								<span
									class="screen-reader-text"><?php esc_html_e('First page', 'obydullah-restaurant-pos-lite'); ?></span>
								<span aria-hidden="true">«</span>
							</a>
							<a class="prev-page button" href="#">
								<span
									class="screen-reader-text"><?php esc_html_e('Previous page', 'obydullah-restaurant-pos-lite'); ?></span>
								<span aria-hidden="true">‹</span>
							</a>
							<span class="paging-input">
								<label for="current-page-selector"
									class="screen-reader-text"><?php esc_html_e('Current Page', 'obydullah-restaurant-pos-lite'); ?></label>
								<input class="current-page" id="current-page-selector" type="text" name="paged" value="1"
									size="3" aria-describedby="table-paging">
								<span class="tablenav-paging-text">
									<?php esc_html_e('of', 'obydullah-restaurant-pos-lite'); ?> <span
										class="total-pages">1</span></span>
							</span>
							<a class="next-page button" href="#">
								<span
									class="screen-reader-text"><?php esc_html_e('Next page', 'obydullah-restaurant-pos-lite'); ?></span>
								<span aria-hidden="true">›</span>
							</a>
							<a class="last-page button" href="#">
								<span
									class="screen-reader-text"><?php esc_html_e('Last page', 'obydullah-restaurant-pos-lite'); ?></span>
								<span aria-hidden="true">»</span>
							</a>
						</span>
					</div>
					<div class="tablenav-pages" style="float:none;">
						<select id="per-page-select" style="padding:4px 16px;border:1px solid #ddd;border-radius:4px;">
							<option value="10">10 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
							<option value="20">20 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
							<option value="50">50 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
							<option value="100">100 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
						</select>
					</div>
				</div>
			</div>
			<script>
				jQuery(document).ready(function ($) {
					let currentPage = 1;
					let perPage = 10;
					let totalPages = 1;
					let totalItems = 0;
					let currentSearch = '';
					let dateFrom = '';
					let dateTo = '';
					let saleType = '';
					let saleStatus = '';

					// Format currency using PHP helper output
					function formatCurrency(amount) {
						// Use the PHP formatted currency as template
						const template = '<?php echo esc_js($this->format_currency(0)); ?>';
						if (amount === null || amount === undefined) return template;
						const amountFormatted = parseFloat(amount).toFixed(2);
						return template.replace('0.00', amountFormatted);
					}

					// Load initial sales
					loadORPLSales();

					// Search functionality
					$('#search-sales').on('click', function () {
						currentSearch = $('#search-invoice').val().trim();
						dateFrom = $('#date-from').val();
						dateTo = $('#date-to').val();
						saleType = $('#sale-type').val();
						saleStatus = $('#sale-status').val();
						currentPage = 1;
						loadORPLSales();
					});

					// Reset filters
					$('#reset-filters').on('click', function () {
						$('#search-invoice').val('');
						$('#date-from').val('');
						$('#date-to').val('');
						$('#sale-type').val('');
						$('#sale-status').val('');
						currentSearch = '';
						dateFrom = '';
						dateTo = '';
						saleType = '';
						saleStatus = '';
						currentPage = 1;
						loadORPLSales();
					});

					// Enter key in search
					$('#search-invoice').on('keypress', function (e) {
						if (e.which === 13) {
							$('#search-sales').click();
						}
					});

					// Per page change
					$('#per-page-select').on('change', function () {
						perPage = parseInt($(this).val());
						loadORPLSales(1);
					});

					// Pagination handlers
					$('.first-page').on('click', function (e) {
						e.preventDefault();
						if (currentPage > 1) loadORPLSales(1);
					});

					$('.prev-page').on('click', function (e) {
						e.preventDefault();
						if (currentPage > 1) loadORPLSales(currentPage - 1);
					});

					$('.next-page').on('click', function (e) {
						e.preventDefault();
						if (currentPage < totalPages) loadORPLSales(currentPage + 1);
					});

					$('.last-page').on('click', function (e) {
						e.preventDefault();
						if (currentPage < totalPages) loadORPLSales(totalPages);
					});

					$('#current-page-selector').on('keypress', function (e) {
						if (e.which === 13) { // Enter key
							let page = parseInt($(this).val());
							if (page >= 1 && page <= totalPages) {
								loadORPLSales(page);
							}
						}
					});

					function loadORPLSales(page = 1) {
						currentPage = page;

						let tbody = $('#sales-list');
						tbody.html('<tr><td colspan="11" class="loading-sales" style="text-align:center;"><span class="spinner is-active"></span> <?php echo esc_js(__('Loading sales...', 'obydullah-restaurant-pos-lite')); ?></td></tr>');

						$.ajax({
							url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
							type: 'GET',
							data: {
								action: 'orpl_get_sales',
								page: currentPage,
								per_page: perPage,
								search: currentSearch,
								date_from: dateFrom,
								date_to: dateTo,
								sale_type: saleType,
								status: saleStatus,
								nonce: '<?php echo esc_js(wp_create_nonce('orpl_get_sales')); ?>'
							},
							success: function (response) {
								let tbody = $('#sales-list').empty();
								if (response.success) {
									updatePagination({
										total_items: response.data.total,
										total_pages: Math.ceil(response.data.total / perPage)
									});

									if (!response.data.sales.length) {
										let message = currentSearch || dateFrom || dateTo || saleType || saleStatus ?
											'<?php echo esc_js(__('No sales found matching your criteria.', 'obydullah-restaurant-pos-lite')); ?>' :
											'<?php echo esc_js(__('No sales found.', 'obydullah-restaurant-pos-lite')); ?>';
										tbody.append('<tr><td colspan="11" style="text-align:center;padding:20px;color:#666;">' + message + '</td></tr>');
										return;
									}

									$.each(response.data.sales, function (_, sale) {
										let row = $('<tr>').attr('data-sale-id', sale.id);

										// Invoice ID
										row.append($('<td>').append(
											$('<strong>').text(sale.invoice_id || '<?php echo esc_js(__('N/A', 'obydullah-restaurant-pos-lite')); ?>')
										));

										// Date
										let saleDate = new Date(sale.created_at);
										let formattedDate = saleDate.toLocaleDateString() + ' ' + saleDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
										row.append($('<td>').text(formattedDate));

										// Customer
										row.append($('<td>').text(sale.customer_name || '<?php echo esc_js(__('Walk-in Customer', 'obydullah-restaurant-pos-lite')); ?>'));

										// Sale Type
										let typeClass = 'sale-type-' + sale.sale_type;
										let typeText = sale.sale_type ?
											sale.sale_type.charAt(0).toUpperCase() + sale.sale_type.slice(1).replace(/([A-Z])/g, ' $1') :
											'<?php echo esc_js(__('N/A', 'obydullah-restaurant-pos-lite')); ?>';
										row.append($('<td>').append(
											$('<span>').addClass(typeClass).text(typeText)
										));

										// Net Price
										row.append($('<td>').text(formatCurrency(sale.net_price)));

										// Tax
										let totalTax = parseFloat(sale.tax_amount || 0);
										row.append($('<td>').text(formatCurrency(totalTax)));

										// VAT
										let totalVat = parseFloat(sale.vat_amount || 0);
										row.append($('<td>').text(formatCurrency(totalVat)));

										// Discount
										row.append($('<td>').text(formatCurrency(sale.discount_amount)));

										// Grand Total
										row.append($('<td>').append(
											$('<strong>').text(formatCurrency(sale.grand_total))
										));

										// Status
										let statusClass = 'status-' + sale.status;
										let statusText = sale.status ?
											sale.status === 'saveSale' ? '<?php echo esc_js(__('Saved', 'obydullah-restaurant-pos-lite')); ?>' :
												sale.status.charAt(0).toUpperCase() + sale.status.slice(1) :
											'<?php echo esc_js(__('N/A', 'obydullah-restaurant-pos-lite')); ?>';
										row.append($('<td>').append(
											$('<span>').addClass(statusClass).text(statusText)
										));

										// Actions
										let actionsTd = $('<td style="text-align:center;">');
										let actionsDiv = $('<div>').addClass('sale-actions');

										// Print button
										actionsDiv.append(
											$('<button>').addClass('button button-small print-sale')
												.text('<?php echo esc_js(__('Print', 'obydullah-restaurant-pos-lite')); ?>')
										);

										// Delete button
										actionsDiv.append(
											$('<button>').addClass('button button-small button-link-delete delete-sale')
												.text('<?php echo esc_js(__('Delete', 'obydullah-restaurant-pos-lite')); ?>')
										);

										actionsTd.append(actionsDiv);
										row.append(actionsTd);

										tbody.append(row);
									});
								} else {
									tbody.append('<tr><td colspan="11" style="color:red;text-align:center;">' + response.data + '</td></tr>');
								}
							},
							error: function () {
								$('#sales-list').html('<tr><td colspan="11" style="color:red;text-align:center;"><?php echo esc_js(__('Failed to load sales.', 'obydullah-restaurant-pos-lite')); ?></td></tr>');
							}
						});
					}

					function updatePagination(pagination) {
						totalPages = pagination.total_pages;
						totalItems = pagination.total_items;

						// Update displaying text
						$('#displaying-num').text(pagination.total_items + ' <?php echo esc_js(__('items', 'obydullah-restaurant-pos-lite')); ?>');

						// Update page input and total pages
						$('#current-page-selector').val(currentPage);
						$('.total-pages').text(totalPages);

						// Update pagination buttons state
						$('.first-page, .prev-page').prop('disabled', currentPage === 1);
						$('.next-page, .last-page').prop('disabled', currentPage === totalPages);
					}

					// Print sale
					$(document).on('click', '.print-sale', function () {
						let saleId = $(this).closest('tr').data('sale-id');

						if (!saleId) {
							alert('<?php echo esc_js(__('Cannot print: No sale ID available', 'obydullah-restaurant-pos-lite')); ?>');
							return;
						}

						$.ajax({
							url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
							type: 'POST',
							data: {
								action: 'orpl_print_sale',
								sale_id: saleId,
								nonce: '<?php echo esc_js(wp_create_nonce('orpl_print_sale')); ?>'
							},
							success: function (response) {
								if (response.success) {
									// Open print window with sale data
									let printWindow = window.open('', '_blank');
									let sale = response.data;
									let shopInfo = <?php echo wp_json_encode($this->get_shop_info()); ?>;

									let printContent = `
<!DOCTYPE html>
<html>
<head>
	<title><?php echo esc_js(__('Invoice', 'obydullah-restaurant-pos-lite')); ?> ${sale.invoice_id || '<?php echo esc_js(__('N/A', 'obydullah-restaurant-pos-lite')); ?>'}</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 20px; }
		.invoice-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
		.shop-info { margin-bottom: 15px; }
		.invoice-details { margin-bottom: 20px; display: flex; justify-content: space-between; }
		.customer-info, .invoice-info { flex: 1; }
		.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
		.items-table th { background: #f8f9fa; border: 1px solid #ddd; padding: 10px; text-align: left; }
		.items-table td { border: 1px solid #ddd; padding: 8px; }
		.summary-table { width: 300px; margin-left: auto; border-collapse: collapse; }
		.summary-table td { padding: 8px; border-bottom: 1px solid #ddd; }
		.summary-table tr.total td { font-weight: bold; border-top: 2px solid #333; }
		.thank-you { text-align: center; margin-top: 30px; font-style: italic; color: #666; }
		@media print { 
			body { margin: 0; font-size: 12px; }
			.invoice-header { border-bottom-color: #000; }
			.summary-table tr.total td { border-top-color: #000; }
		}
	</style>
</head>
<body>
	<div class="invoice-header">
		<div class="shop-info">
			${shopInfo.name ? `<h2>${shopInfo.name}</h2>` : ''}
			${shopInfo.address ? `<p>${shopInfo.address}</p>` : ''}
			${shopInfo.phone ? `<p><?php echo esc_js(__('Phone:', 'obydullah-restaurant-pos-lite')); ?> ${shopInfo.phone}</p>` : ''}
		</div>
		<h3><?php echo esc_js(__('INVOICE', 'obydullah-restaurant-pos-lite')); ?> #${sale.invoice_id || '<?php echo esc_js(__('N/A', 'obydullah-restaurant-pos-lite')); ?>'}</h3>
	</div>
	
	<div class="invoice-details">
		<div class="customer-info">
			<p><strong><?php echo esc_js(__('Customer:', 'obydullah-restaurant-pos-lite')); ?></strong> ${sale.customer_name || '<?php echo esc_js(__('Walk-in Customer', 'obydullah-restaurant-pos-lite')); ?>'}</p>
			${sale.customer_mobile ? `
			<p><strong><?php echo esc_js(__('Mobile:', 'obydullah-restaurant-pos-lite')); ?></strong> ${sale.customer_mobile}</p>
			` : ''}
			${sale.customer_email ? `
			<p><strong><?php echo esc_js(__('Email:', 'obydullah-restaurant-pos-lite')); ?></strong> ${sale.customer_email}</p>
			` : ''}
			${sale.customer_address ? `
			<p><strong><?php echo esc_js(__('Address:', 'obydullah-restaurant-pos-lite')); ?></strong> ${sale.customer_address}</p>
			` : ''}
			<p><strong><?php echo esc_js(__('Order Type:', 'obydullah-restaurant-pos-lite')); ?></strong> ${sale.sale_type ? sale.sale_type.charAt(0).toUpperCase() + sale.sale_type.slice(1).replace(/([A-Z])/g, ' $1') : '<?php echo esc_js(__('N/A', 'obydullah-restaurant-pos-lite')); ?>'}</p>
			${sale.cooking_instructions ? `<p><strong><?php echo esc_js(__('Cooking Instructions:', 'obydullah-restaurant-pos-lite')); ?></strong> ${sale.cooking_instructions}</p>` : ''}
		</div>
		<div class="invoice-info">
			<p><strong><?php echo esc_js(__('Date:', 'obydullah-restaurant-pos-lite')); ?></strong> ${new Date(sale.created_at).toLocaleString()}</p>
			<p><strong><?php echo esc_js(__('Status:', 'obydullah-restaurant-pos-lite')); ?></strong> ${sale.status ? sale.status.charAt(0).toUpperCase() + sale.status.slice(1) : '<?php echo esc_js(__('N/A', 'obydullah-restaurant-pos-lite')); ?>'}</p>
		</div>
	</div>
	
	<table class="items-table">
		<thead>
			<tr>
				<th><?php echo esc_js(__('Item', 'obydullah-restaurant-pos-lite')); ?></th>
				<th><?php echo esc_js(__('Quantity', 'obydullah-restaurant-pos-lite')); ?></th>
				<th><?php echo esc_js(__('Price', 'obydullah-restaurant-pos-lite')); ?></th>
				<th><?php echo esc_js(__('Total', 'obydullah-restaurant-pos-lite')); ?></th>
			</tr>
		</thead>
		<tbody>
			${sale.items && sale.items.length > 0 ? sale.items.map(item => `
				<tr>
					<td>${item.product_name}</td>
					<td>${item.quantity}</td>
					<td>${formatCurrency(item.unit_price)}</td>
					<td>${formatCurrency(item.total_price)}</td>
				</tr>
			`).join('') : '<tr><td colspan="4" style="text-align:center;"><?php echo esc_js(__('No items found', 'obydullah-restaurant-pos-lite')); ?></td></tr>'}
		</tbody>
	</table>
	
	<table class="summary-table">
		<tr>
			<td><?php echo esc_js(__('Net Price:', 'obydullah-restaurant-pos-lite')); ?></td>
			<td>${formatCurrency(sale.net_price || 0)}</td>
		</tr>
		<tr>
			<td><?php echo esc_js(__('Tax:', 'obydullah-restaurant-pos-lite')); ?></td>
			<td>${formatCurrency((parseFloat(sale.tax_amount || 0)))}</td>
		</tr>
		<tr>
			<td><?php echo esc_js(__('VAT:', 'obydullah-restaurant-pos-lite')); ?></td>
			<td>${formatCurrency((parseFloat(sale.vat_amount || 0)))}</td>
		</tr>
		<tr>
			<td><?php echo esc_js(__('Shipping:', 'obydullah-restaurant-pos-lite')); ?></td>
			<td>${formatCurrency(sale.shipping_cost || 0)}</td>
		</tr>
		<tr>
			<td><?php echo esc_js(__('Discount:', 'obydullah-restaurant-pos-lite')); ?></td>
			<td>${formatCurrency(sale.discount_amount || 0)}</td>
		</tr>
		<tr class="total">
			<td><?php echo esc_js(__('Grand Total:', 'obydullah-restaurant-pos-lite')); ?></td>
			<td>${formatCurrency(sale.grand_total || 0)}</td>
		</tr>
	</table>
	
	${sale.note ? `<div style="margin-top: 20px;"><strong><?php echo esc_js(__('Note:', 'obydullah-restaurant-pos-lite')); ?></strong> ${sale.note}</div>` : ''}
</body>
</html>
									`;

							printWindow.document.write(printContent);
							printWindow.document.close();
							printWindow.focus();
							printWindow.print();
						} else {
							alert('<?php echo esc_js(__('Error:', 'obydullah-restaurant-pos-lite')); ?> ' + response.data);
						}
					},
					error: function (xhr, status, error) {
						alert('<?php echo esc_js(__('Request failed:', 'obydullah-restaurant-pos-lite')); ?> ' + error);
					}
				});
			});

			// Delete sale
			$(document).on('click', '.delete-sale', function () {
				if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this sale? This action cannot be undone.', 'obydullah-restaurant-pos-lite')); ?>')) return;

				let button = $(this);
				let originalText = button.text();
				let id = $(this).closest('tr').data('sale-id');

				// Disable button and show loading
				button.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'obydullah-restaurant-pos-lite')); ?>');

				$.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
					action: 'orpl_delete_sale',
					id: id,
					nonce: '<?php echo esc_js(wp_create_nonce('orpl_delete_sale')); ?>'
				}, function (res) {
					if (res.success) {
						loadORPLSales(currentPage);
					} else {
						alert(res.data);
					}
				}).fail(() => alert('<?php echo esc_js(__('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite')); ?>'))
					.always(function () {
						// Re-enable button
						button.prop('disabled', false).text(originalText);
					});
			});
		});
	</script>
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