// Generated by BUCKLESCRIPT, PLEASE EDIT WITH CARE

import * as React from "react";
import * as Caml_option from "bs-platform/lib/es6/caml_option.js";
import * as Loadable$WoocommercePayments from "./loadable.bs.js";

function Loadable_block(Props) {
  var numLinesOpt = Props.numLines;
  var isLoading = Props.isLoading;
  var value = Props.value;
  var children = Props.children;
  var numLines = numLinesOpt !== undefined ? numLinesOpt : 1;
  var placeholder = Caml_option.some(React.createElement("p", {
            style: {
              lineHeight: String(numLines)
            }
          }, "Block placeholder"));
  return React.createElement(Loadable$WoocommercePayments.make, {
              isLoading: isLoading,
              display: "block",
              placeholder: placeholder,
              value: value,
              children: children
            });
}

var make = Loadable_block;

var $$default = Loadable_block;

export {
  make ,
  $$default ,
  $$default as default,
  
}
/* react Not a pure module */