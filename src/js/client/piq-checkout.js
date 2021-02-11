import { helloWorld } from './../utils'
import _PaymentIQCashier from 'paymentiq-cashier-bootstrapper'

window.addEventListener('load', function () {
  helloWorld()
});

console.log('Register event listener')
window.addEventListener('message', function (e) {
  if (e.data && e.data.eventType) {
    const { eventType, payload } = e.data
    switch (eventType) {
      case '::wooCommerceSetupPIQCheckout':
        return setupCheckout(payload)
      default: 
        return
    }
  }
})

function setupCheckout (payload) {
  // allow deletion of object properties - strict mode blocks this in some browsers, so we create a new object
  let appConfig = {
    ...payload
  }

  const orderId = appConfig.attributes.orderId
  const orderKey = appConfig.orderKey // need this to deal with the redirect to thank-you page
  delete appConfig.orderKey
  
  let orderItems = appConfig.orderItems
  orderItems = JSON.parse(orderItems) // for some reason delete payload.orderItems fails otherwise in safari
  delete appConfig.orderItems
  
  const freightFee = appConfig.freightFee
  delete appConfig.freightFee
  
  const checkDeviceId = appConfig.checkUserDevice
  delete appConfig.checkUserDevice
  
  const country = appConfig.country
  delete appConfig.country
  
  const didClientId = appConfig.didClientId
  delete appConfig.didClientId
  
  const buttonsColor = appConfig.buttonsColor
  delete appConfig.buttonsColor

  const lookupConfig = {
    didClientId,
    country: country,
    identifyFields: 'zip,email',
    environment: appConfig.environment,
    checkUserDevice: checkDeviceId,
    owner: 'Santander'
  }
  
  const config = {
    environment: appConfig.environment,
    "showAccounts": "inline",
    "globalSubmit": true,
    "showListHeaders": true,
    "mode": "ecommerce",
    "font": 'custom,santander,santander',
    "showReceipt": false, // we redirect to order-received page right away instead
    "fetchConfig": true,
    "containerHeight": 'auto',
    "containerMinHeight": '600px',
    lookupConfig: {
      ...lookupConfig
    },
    theme: {
      buttons: {
        color: buttonsColor
      }
    },
    ...appConfig
  }

  renderCheckout({ config, orderItems, orderKey, orderId, freightFee })
}

function renderCheckout ({ config, orderItems, orderKey, orderId, freightFee }) {
  if (!_PaymentIQCashier) {
    setTimeout(function () {
      renderCheckout({ config, orderItems, orderKey, orderId, freightFee })
    }, 100)
  } else {
    // We need to keep track when the user cancels a provider flow. When that happens, we're gonna end up with the same
    // orderId but we're gonna have a transaction in PIQ already tied to that orderId. Initiating a second transaction
    // will cause them to get mixed up. So, when that happens, reload the page to trigger a new orderId.
    let providerWasOpened = false

    new _PaymentIQCashier('#piq-checkout', config, (api) => {
      api.on({
        cashierInitLoad: () => {
          api.set({
            order: {
              orderItems: orderItems,
              freightFee
            }
          })
          document.getElementById('lookupIframe').scrollIntoView()
        },
        success: data => notifyOrderStatus('success', orderId, orderKey, data),
        failure: data => notifyOrderStatus('failure', orderId, orderKey, data),
        pending: data => notifyOrderStatus('pending', orderId, orderKey, data),
        newProviderWindow: data => {
          providerWasOpened = true
          if (data.data === 'NEW_IFRAME') {
            document.getElementById('cashierIframe').scrollIntoView()
          }
        },
        navigate: data => {
          if (providerWasOpened && data.data.path === '/') {
            // user clicked back during the provider flow, meaning we end up with an orderId + an initiated transaction in PIQ
            // In this case, we must generate a new orderId which we can do by reloading the page.
            location.reload()
          }
        }
      });
    })
  }
}

/* We need to give back control to the script in the php-code
   We do this via a postMessage back (templates/Checkout/paymentiq-checkout.php)
*/
function notifyOrderStatus (status, orderId, orderKey, data) {
  console.log('notifyOrderStatus')
  let payload = {}
  switch (status) {
    case 'success':
      payload = {
        eventType: '::wooCommercePaymentSuccess',
        payload: {
          orderId,
          ...data
        }
      }
      window.location.href = `/checkout/order-received/${orderId}?key=${orderKey}`
      break
    case 'failure':
      payload = {
        eventType: '::wooCommercePaymentFailure',
        payload: {
          orderId,
          ...data
        }
      }
      // window.location.href = `/checkout/order-received/${orderId}?key=${orderKey}`
      break
    case 'pending':
      payload = {
        eventType: '::wooCommercePaymentPending',
        payload: {
          orderId,
          ...data
        }
      }
      window.location.href = `/checkout/order-received/${orderId}?key=${orderKey}`
      break
    default:
      return
  }
  window.postMessage(payload, '*')
}
