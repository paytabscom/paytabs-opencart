<?php

class ControllerPaytabsOrder extends ControllerSaleOrder {
     
    public function info() {
        
        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } 

        $payment_method =  $this->db->query("SELECT pt_payment_method FROM " . DB_PREFIX . "paytabs_transaction_reference WHERE order_id = '" . (int)$order_id . "'")->row;

        $order_status_id = $this->db->query("SELECT order_status_id FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'")->row;

       
        $refund_url = "extension/payment/paytabs_".implode(" ",$payment_method)."/refund";
        $data['refund'] = $this->url->link($refund_url, 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true);
       

        // Start capturing the output into a buffer
        ob_start();

        // Execute the parent::info() method
        $parentOutput = parent::info();

        // Get the captured output from the buffer
        $parentView = ob_get_clean();


        if($order_status_id['order_status_id'] == 11)
        {
            // Append your custom button to the captured output
            $customButton = '<p  style = "cursor: auto; position: absolute; display: block; z-index: 11111111; right: 12%; top: 8%;" class="btn btn-primary"> 
            Refunded to Paytabs </p>';
             
        }
        else
        {
             // Append your custom button to the captured output
             $customButton = '<a  style = "position: absolute; display: block; z-index: 11111111; right: 12%; top: 8%;"href="' . $data['refund'] . '" class="btn btn-primary"><i class="fa fa-undo"></i> refund to paytabs</a>';

        }

        $modifiedOutput = str_replace('</div>', $customButton . '</div>', $parentView);

        // Output the modified content
        echo $customButton;

        // Return the parent output
        return $modifiedOutput;

    }

    
}