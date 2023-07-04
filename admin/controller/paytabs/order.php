<?php

class ControllerPaytabsOrder extends ControllerSaleOrder
{
    public function info()
    {
        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        }

        if (!$order_id) {
            return;
        }

        $order_info = $this->_order_details($order_id);
        if (!$order_info) {
            return;
        }

        $order_status_id = $order_info['order_status_id'];
        $payment_method = $order_info['payment_method'];
        $payment_code = $order_info['payment_code'];

        $trx_payment_code = $this->_get_pt_transaction($order_id);

        // ToDo
        // Confirm if the plugin is installed & the table is exists
        if (!$this->_is_pt_order($payment_code, $trx_payment_code)) {
            return;
        }



        $refund_url = "extension/payment/paytabs_" . $payment_code . "/refund";
        $data['refund'] = $this->url->link($refund_url, 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true);


        // ToDo
        // Use payment_code instead of payment_method

        if (strpos($payment_method, "PayTabs") !== false) {

            $this->_append_refund_btn($order_status_id,$data);
        }
    }


    private function _order_details($order_id)
    {
        $_query_columns = 'order_status_id, payment_method, payment_code';
        $order_info =  $this->db->query("SELECT {$_query_columns} FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'")->row;

        if (empty($order_info)) {
            return false;
        }

        return $order_info;
    }

    // ToDo
    // 1. Get only latest Success, Sale or Captured trx
    // 2. Return only one string
    // 3. Validate the result
    private function _get_pt_transaction($order_id)
    {
        $payment_code =  $this->db->query("SELECT pt_payment_method FROM " . DB_PREFIX . "pt_transaction_reference WHERE order_id = '" . (int)$order_id . "'")->row;

        $payment_code = implode(" ", $payment_code);

        return $payment_code;
    }

    /**
     * Check if the Order paid by PayTabs
     * Check if PT is already installed & the pt table exists
     */
    private function _is_pt_order($order_payment_code, $trx_payment_code)
    {
        // PaytabsHelper::isPayTabsPayment($order_payment_code);
        // PaytabsHelper::isPayTabsPayment($trx_payment_code);
        return true;
    }

    private function _append_refund_btn($order_status_id,$data)
    {
         // Start capturing the output into a buffer
         ob_start();

         // Execute the parent::info() method
         $parentOutput = parent::info();

         // Get the captured output from the buffer
         $parentView = ob_get_clean();


         if ($order_status_id == 11) {
             // Append your custom button to the captured output
             $customButton = '<p style="cursor: auto; position: absolute; display: block; z-index: 11111111; right: 12%; top: 8%;" class="btn btn-primary"> 
             Refunded to Paytabs </p>';
         } else {
             // Append your custom button to the captured output
             $customButton = '<a style="position: absolute; display: block; z-index: 11111111; right: 12%; top: 8%;"href="' . $data['refund'] . '" class="btn btn-primary"><i class="fa fa-undo"></i>Refund using PayTabs</a>';
         }

         $modifiedOutput = str_replace('</div>', $customButton . '</div>', $parentView);

         // Output the modified content
         echo $customButton;

         // Return the parent output
         return $modifiedOutput;
    }
}
