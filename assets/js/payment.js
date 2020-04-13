const TransactionTimeoutTime = 60000;
const TransactionStatusTime = 3000;
//TODO: implementar contador visivel

  let app = new Vue({
    el: '#app',
    data: {
      status:payment_text.intro,
      timerChecker : null,
      timeoutChecker: null,
      return_url: '#',
      disabled: false
      },
      methods:{
        requestSyncPayment: function (info) {
          this.tooglePaymentButton();
          this.return_url = info.return_url;
          const params = new URLSearchParams();
          params.append('order_id',info.order_id);
          
          this.checkTimeout();
          axios.post('/?wc-api=process_action', params).then(function (response) {
            if(response.data.status == 'success'){
              this.status = text.received;
              setTimeout(()=> (window.location.href = this.return_url) , 5000);
            }else if(response.data.status == 'failed') {
              this.status = text.failed;
            }
          }.bind(this))
        },
        tooglePaymentButton: function () {
        this.disabled = (!this.disabled)
      },
        checkTimeout: function () {
          this.timeoutChecker = setTimeout( () => {
            this.status = text.timeout;
            clearInterval(this.timerChecker)
          }, TransactionTimeoutTime)
        }
      }  
  })