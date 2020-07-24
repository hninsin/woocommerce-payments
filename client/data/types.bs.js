// Generated by BUCKLESCRIPT, PLEASE EDIT WITH CARE

import * as Caml_option from "bs-platform/lib/es6/caml_option.js";

function make(cityOpt, countryOpt, line1Opt, line2Opt, postal_codeOpt, stateOpt, param) {
  var city = cityOpt !== undefined ? Caml_option.valFromOption(cityOpt) : undefined;
  var country = countryOpt !== undefined ? Caml_option.valFromOption(countryOpt) : undefined;
  var line1 = line1Opt !== undefined ? Caml_option.valFromOption(line1Opt) : undefined;
  var line2 = line2Opt !== undefined ? Caml_option.valFromOption(line2Opt) : undefined;
  var postal_code = postal_codeOpt !== undefined ? Caml_option.valFromOption(postal_codeOpt) : undefined;
  var state = stateOpt !== undefined ? Caml_option.valFromOption(stateOpt) : undefined;
  return {
          city: city,
          country: country,
          line1: line1,
          line2: line2,
          postal_code: postal_code,
          state: state
        };
}

var Address = {
  make: make
};

function make$1(addressOpt, emailOpt, nameOpt, phoneOpt, formatted_addressOpt, param) {
  var address = addressOpt !== undefined ? addressOpt : make(undefined, undefined, undefined, undefined, undefined, undefined, undefined);
  var email = emailOpt !== undefined ? Caml_option.valFromOption(emailOpt) : undefined;
  var name = nameOpt !== undefined ? Caml_option.valFromOption(nameOpt) : undefined;
  var phone = phoneOpt !== undefined ? Caml_option.valFromOption(phoneOpt) : undefined;
  var formatted_address = formatted_addressOpt !== undefined ? Caml_option.valFromOption(formatted_addressOpt) : undefined;
  return {
          address: address,
          email: email,
          name: name,
          phone: phone,
          formatted_address: formatted_address
        };
}

var BillingDetails = {
  make: make$1
};

function make$2(urlOpt, numberOpt, param) {
  var url = urlOpt !== undefined ? urlOpt : "";
  var number = numberOpt !== undefined ? numberOpt : 0;
  return {
          url: url,
          number: number
        };
}

var Order = {
  make: make$2
};

function make$3(discount_amountOpt, product_codeOpt, product_descriptionOpt, quantityOpt, tax_amountOpt, unit_costOpt, param) {
  var discount_amount = discount_amountOpt !== undefined ? discount_amountOpt : 0;
  var product_code = product_codeOpt !== undefined ? product_codeOpt : "";
  var product_description = product_descriptionOpt !== undefined ? product_descriptionOpt : "";
  var quantity = quantityOpt !== undefined ? quantityOpt : 0;
  var tax_amount = tax_amountOpt !== undefined ? tax_amountOpt : 0;
  var unit_cost = unit_costOpt !== undefined ? unit_costOpt : 0;
  return {
          discount_amount: discount_amount,
          product_code: product_code,
          product_description: product_description,
          quantity: quantity,
          tax_amount: tax_amount,
          unit_cost: unit_cost
        };
}

var Level3LineItem = {
  make: make$3
};

function make$4(line_itemsOpt, merchant_referenceOpt, shipping_address_zipOpt, shipping_amountOpt, shipping_from_zipOpt, param) {
  var line_items = line_itemsOpt !== undefined ? line_itemsOpt : [];
  var merchant_reference = merchant_referenceOpt !== undefined ? merchant_referenceOpt : "";
  var shipping_address_zip = shipping_address_zipOpt !== undefined ? shipping_address_zipOpt : "";
  var shipping_amount = shipping_amountOpt !== undefined ? shipping_amountOpt : 0;
  var shipping_from_zip = shipping_from_zipOpt !== undefined ? shipping_from_zipOpt : "";
  return {
          line_items: line_items,
          merchant_reference: merchant_reference,
          shipping_address_zip: shipping_address_zip,
          shipping_amount: shipping_amount,
          shipping_from_zip: shipping_from_zip
        };
}

var Level3 = {
  make: make$4
};

function make$5(statusOpt, param) {
  var status = statusOpt !== undefined ? statusOpt : "";
  return {
          status: status
        };
}

var Dispute = {
  make: make$5
};

function make$6(type_Opt, risk_levelOpt, param) {
  var type_ = type_Opt !== undefined ? type_Opt : "";
  var risk_level = risk_levelOpt !== undefined ? risk_levelOpt : "";
  return {
          type: type_,
          risk_level: risk_level
        };
}

var Outcome = {
  make: make$6
};

var Refund = {};

function make$7(object_Opt, dataOpt, has_moreOpt, total_countOpt, urlOpt, param) {
  var object_ = object_Opt !== undefined ? object_Opt : "";
  var data = dataOpt !== undefined ? dataOpt : [];
  var has_more = has_moreOpt !== undefined ? has_moreOpt : false;
  var total_count = total_countOpt !== undefined ? total_countOpt : 0;
  var url = urlOpt !== undefined ? urlOpt : "";
  return {
          object: object_,
          data: data,
          has_more: has_more,
          total_count: total_count,
          url: url
        };
}

var Refunds = {
  make: make$7
};

function make_checks(address_line1_checkOpt, address_postal_code_checkOpt, cvc_checkOpt, param) {
  var address_line1_check = address_line1_checkOpt !== undefined ? Caml_option.valFromOption(address_line1_checkOpt) : undefined;
  var address_postal_code_check = address_postal_code_checkOpt !== undefined ? Caml_option.valFromOption(address_postal_code_checkOpt) : undefined;
  var cvc_check = cvc_checkOpt !== undefined ? cvc_checkOpt : "";
  return {
          address_line1_check: address_line1_check,
          address_postal_code_check: address_postal_code_check,
          cvc_check: cvc_check
        };
}

function make$8(checksOpt, countryOpt, exp_monthOpt, exp_yearOpt, fingerprintOpt, fundingOpt, last4Opt, networkOpt, param) {
  var checks = checksOpt !== undefined ? checksOpt : make_checks(undefined, undefined, undefined, undefined);
  var country = countryOpt !== undefined ? countryOpt : "";
  var exp_month = exp_monthOpt !== undefined ? exp_monthOpt : 0;
  var exp_year = exp_yearOpt !== undefined ? exp_yearOpt : 0;
  var fingerprint = fingerprintOpt !== undefined ? fingerprintOpt : "";
  var funding = fundingOpt !== undefined ? fundingOpt : "";
  var last4 = last4Opt !== undefined ? last4Opt : "";
  var network = networkOpt !== undefined ? networkOpt : "";
  return {
          checks: checks,
          country: country,
          exp_month: exp_month,
          exp_year: exp_year,
          fingerprint: fingerprint,
          funding: funding,
          last4: last4,
          network: network
        };
}

var Card = {
  make_checks: make_checks,
  make: make$8
};

function make$9(cardOpt, type_Opt, param) {
  var card = cardOpt !== undefined ? cardOpt : make$8(undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined);
  var type_ = type_Opt !== undefined ? type_Opt : "";
  return {
          card: card,
          type: type_
        };
}

var PaymentMethodDetails = {
  make: make$9
};

var Metadata = {};

function make$10(idOpt, object_Opt, amountOpt, amount_refundedOpt, applicationOpt, application_feeOpt, application_fee_amountOpt, balance_transactionOpt, billing_detailsOpt, calculated_statement_descriptorOpt, capturedOpt, createdOpt, currencyOpt, disputeOpt, disputedOpt, level3Opt, livemodeOpt, outcomeOpt, paidOpt, payment_intentOpt, payment_methodOpt, payment_method_detailsOpt, receipt_emailOpt, receipt_numberOpt, receipt_urlOpt, refundedOpt, refundsOpt, statusOpt, param) {
  var id = idOpt !== undefined ? idOpt : "";
  var object_ = object_Opt !== undefined ? object_Opt : "";
  var amount = amountOpt !== undefined ? amountOpt : 0;
  var amount_refunded = amount_refundedOpt !== undefined ? amount_refundedOpt : 0;
  var application = applicationOpt !== undefined ? Caml_option.valFromOption(applicationOpt) : undefined;
  var application_fee = application_feeOpt !== undefined ? Caml_option.valFromOption(application_feeOpt) : undefined;
  var application_fee_amount = application_fee_amountOpt !== undefined ? Caml_option.valFromOption(application_fee_amountOpt) : undefined;
  var balance_transaction = balance_transactionOpt !== undefined ? balance_transactionOpt : "";
  var billing_details = billing_detailsOpt !== undefined ? billing_detailsOpt : make$1(undefined, undefined, undefined, undefined, undefined, undefined);
  var calculated_statement_descriptor = calculated_statement_descriptorOpt !== undefined ? Caml_option.valFromOption(calculated_statement_descriptorOpt) : undefined;
  var captured = capturedOpt !== undefined ? capturedOpt : false;
  var created = createdOpt !== undefined ? createdOpt : 0;
  var currency = currencyOpt !== undefined ? currencyOpt : "";
  var dispute = disputeOpt !== undefined ? Caml_option.valFromOption(disputeOpt) : undefined;
  var disputed = disputedOpt !== undefined ? disputedOpt : false;
  var level3 = level3Opt !== undefined ? Caml_option.valFromOption(level3Opt) : undefined;
  var livemode = livemodeOpt !== undefined ? livemodeOpt : false;
  var outcome = outcomeOpt !== undefined ? Caml_option.valFromOption(outcomeOpt) : undefined;
  var paid = paidOpt !== undefined ? paidOpt : false;
  var payment_intent = payment_intentOpt !== undefined ? Caml_option.valFromOption(payment_intentOpt) : undefined;
  var payment_method = payment_methodOpt !== undefined ? payment_methodOpt : "";
  var payment_method_details = payment_method_detailsOpt !== undefined ? payment_method_detailsOpt : make$9(undefined, undefined, undefined);
  var receipt_email = receipt_emailOpt !== undefined ? Caml_option.valFromOption(receipt_emailOpt) : undefined;
  var receipt_number = receipt_numberOpt !== undefined ? Caml_option.valFromOption(receipt_numberOpt) : undefined;
  var receipt_url = receipt_urlOpt !== undefined ? receipt_urlOpt : "";
  var refunded = refundedOpt !== undefined ? refundedOpt : false;
  var refunds = refundsOpt !== undefined ? Caml_option.valFromOption(refundsOpt) : undefined;
  var status = statusOpt !== undefined ? statusOpt : "";
  return {
          id: id,
          object: object_,
          amount: amount,
          amount_refunded: amount_refunded,
          application: application,
          application_fee: application_fee,
          application_fee_amount: application_fee_amount,
          balance_transaction: balance_transaction,
          billing_details: billing_details,
          calculated_statement_descriptor: calculated_statement_descriptor,
          captured: captured,
          created: created,
          currency: currency,
          dispute: dispute,
          disputed: disputed,
          level3: level3,
          livemode: livemode,
          outcome: outcome,
          paid: paid,
          payment_intent: payment_intent,
          payment_method: payment_method,
          payment_method_details: payment_method_details,
          receipt_email: receipt_email,
          receipt_number: receipt_number,
          receipt_url: receipt_url,
          refunded: refunded,
          refunds: refunds,
          status: status
        };
}

var RequestError = {};

var $$Request = {};

var Charge = {
  Metadata: Metadata,
  make: make$10,
  RequestError: RequestError,
  $$Request: $$Request
};

var $$Event = {};

var State = {};

var ChargeReducer = {
  $$Event: $$Event,
  State: State
};

var Reducer = {};

export {
  Address ,
  BillingDetails ,
  Order ,
  Level3LineItem ,
  Level3 ,
  Dispute ,
  Outcome ,
  Refund ,
  Refunds ,
  Card ,
  PaymentMethodDetails ,
  Charge ,
  ChargeReducer ,
  Reducer ,
  
}
/* No side effect */