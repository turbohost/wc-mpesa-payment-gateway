const TransactionTimeoutTime = 60000;
//TODO: implementar contador visivel

let app = new Vue({
  el: '#app',
  data: {
    status: payment_text.status.intro,
    timerChecker: null,
    timeoutChecker: null,
    return_url: '#',
    disabled: false,
    error,
  },
  methods: {
    requestSyncPayment: function (info) {
      this.tooglePaymentButton();
      this.return_url = info.return_url;
      const params = new URLSearchParams();
      params.append('order_id', info.order_id);
      this.checkTimeout();
      this.status = payment_text.status.requested;
      axios.post('?wc-api=process_action', params).then(function (response) {
        if (response.data.status == 'success') {
          this.status = payment_text.status.received;
          setTimeout(() => (window.location.href = this.return_url), 5000);
        } else if (response.data.status == 'failed') {
          this.status = payment_text.status.failed;
          switch (data.status_code) {
            //show detailed error message
            case 'INS-13':
              this.error  = payment_text.errors.invalid_shortcode;
              break;
            case 'INS-16':
              this.error  = payment_text.errors.server_down;
              break;
            case 'INS-996':
              this.error  = payment_text.errors.account_inactive;
              break;
            case 'INS-2001':
              this.error  = payment_text.errors.auth_failed;
              break;
            case 'INS-2006':
              this.error  = payment_text.errors.no_balance;
              break;
            default:
              break;
          }

        }
      }.bind(this))
    },
    tooglePaymentButton: function () {
      this.disabled = (!this.disabled)
    },
    checkTimeout: function () {
      this.timeoutChecker = setTimeout(() => {
        this.status = payment_text.status.timeout;
        clearInterval(this.timerChecker)
      }, TransactionTimeoutTime)
    }
  }
})