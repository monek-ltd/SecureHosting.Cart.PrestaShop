<html>
<body>{$redirect_text}
<form action="{$securehosting_url}" id="SecureHosting_checkout" name="SecureHosting_checkout" method="POST">
    <input id="shreference" name="shreference" value="{$shreference}" type="hidden"/>
    <input id="checkcode" name="checkcode" value="{$checkcode}" type="hidden"/>
    <input id="filename" name="filename" value="{$template}" type="hidden"/>
    <input id="secuitems" name="secuitems" value="{$products}" type="hidden"/>

    <input id="orderid" name="orderid" value="{$cart_id}" type="hidden"/>

    <input id="transactionamount" name="transactionamount" value="{$transactionamount}" type="hidden"/>
    <input id="products_price" name="subtotal" value="{$products_amount}" type="hidden"/>
    <input id="transactiontax" name="transactiontax" value="{$tax}" type="hidden"/>
    <input id="shippingcharge" name="shippingcharge" value="{$shipping}" type="hidden"/>
    <input id="transactioncurrency" name="transactioncurrency" value="{$currency}" type="hidden"/>

    <input id="cardholdersname" name="cardholdersname" value="{$billing_address->firstname} {$billing_address->lastname}" type="hidden"/>
    <input id="cardholderaddr1" name="cardholderaddr1" value="{$billing_address->address1} {$billing_address->address2}" type="hidden"/>
    <input id="cardholdercity" name="cardholdercity" value="{$billing_address->city}" type="hidden"/>
    <input id="cardholderstate" name="cardholderstate" value="{$billing_state}" type="hidden"/>
    <input id="cardholderpostcode" name="cardholderpostcode" value="{$billing_address->postcode}" type="hidden"/>
    <input id="cardholdercountry" name="cardholdercountry" value="{$billing_country->iso_code}" type="hidden"/>
    <input id="cardholdertelephonenumber" name="cardholdertelephonenumber" value="{$billing_address->phone}" type="hidden"/>
    <input id="htmlbad_override" name="htmlbad_override" value="htmlbad.html"
    <input id="cardholdersemail" name="cardholdersemail" value="{$email}" type="hidden"/>

    <input id="success_url" name="success_url" value="{$success_url}" type="hidden"/>
    <input id="failure_url" name="failure_url" value="{$failure_url}" type="hidden"/>
    <input id="callbackurl" name="callbackurl" value="{$callbackurl}" type="hidden"/>
    <input id="callbackdata" name="callbackdata" value="{$callbackdata}" type="hidden"/>
    <input id="secuString" name="secuString" value="{$secuString}" type="hidden"/>
</form>

<script type="text/javascript">document.getElementById("SecureHosting_checkout").submit();</script>
{**}
</body>
</html>
