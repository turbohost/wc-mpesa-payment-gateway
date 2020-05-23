<div class="payment-container" id="app">
  <div>
    <h4 class="payment-title" v-cloak>{{status.title}}</h4>
    <div v-if="error" class="payment-error" role="error">{{error}}</div>
    <div class="payment-description" role="alert" v-html="status.description"></div>
  </div>
  <button class="payment-btn" v-bind="{ disabled }" v-on:click="requestSyncPayment({
        order_id: '<?= $order_id ?>',
        return_url: '<?= $return_url ?>',
    })">
    <?= __('Pay', 'wc-mpesa-payment-gateway') ?>
  </button>
</div>