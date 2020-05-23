const TransactionTimeoutTime = 60000;
//TODO: implement visual counter

let app = new Vue({
  el: '#app',
  data: {
    status: payment_text.status.intro,
    timerChecker: null,
    timeoutChecker: null,
    return_url: '#',
    disabled: false,
    error: null,
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
          this.error = response.data.error_message;
          clearInterval(this.timerChecker);
        }
      }.bind(this))
    },
    tooglePaymentButton: function () {
      this.disabled = (!this.disabled)
    },
    checkTimeout: function () {
      this.timeoutChecker = setTimeout(() => {
        this.status = payment_text.status.timeout;
        clearInterval(this.timerChecker);
      }, TransactionTimeoutTime)
    }
  }
})