// Generated by BUCKLESCRIPT, PLEASE EDIT WITH CARE

import * as Belt_MapString from "bs-platform/lib/es6/belt_MapString.js";
import * as Types$WoocommercePayments from "../types.bs.js";

function getCharge(state, id) {
  return Belt_MapString.getWithDefault(state.charges, id, {
              data: Types$WoocommercePayments.Charge.make(undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined),
              error: undefined
            }).data;
}

function getChargeError(state, id) {
  return Belt_MapString.getWithDefault(state.charges, id, {
              data: undefined,
              error: undefined
            }).error;
}

export {
  getCharge ,
  getChargeError ,
  
}
/* No side effect */