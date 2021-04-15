<div class="payment-container" id="app">
  <div>
    <h4 class="payment-title" v-cloak>{{status.title}}</h4>
    <div v-if="error" class="payment-error" role="error">{{error}}</div>
    <div class="payment-description" role="alert" v-html="status.description"></div>
  </div>
  <button class="payment-btn" v-bind="{ btnDisabled }" v-on:click='pay(<?= $data ?>)'>
    <?= __('Pay', 'wc-mpesa-payment-gateway') ?>
  </button>
</div>