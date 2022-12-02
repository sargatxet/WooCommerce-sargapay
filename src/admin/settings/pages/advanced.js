/*WordPress*/
import { useContext } from "@wordpress/element"
import { __ } from "@wordpress/i18n"
import { RangeControl } from "@wordpress/components"
import { __experimentalNumberControl as NumberControl } from "@wordpress/components"

/*Inbuilt Context*/
import { SettingsContext } from "../../../context/SettingsContext.js"

const Advanced = () => {
  const { useSettings, useUpdateStateSettings } = useContext(SettingsContext)

  return (
    <>
      <div className="wp-sargapay-plugin-field-wrap">
        <RangeControl
          help={__(
            "Add a % to exchange rate from fiat to crypto calculation",
            "sargapay"
          )}
          initialPosition={
            useSettings && useSettings["markup"]
              ? parseInt(useSettings["markup"])
              : 10
          }
          label={__("Add Markup", "sargapay")}
          max={100}
          min={-100}
          onChange={newVal => useUpdateStateSettings("markup", newVal)}
        />
      </div>
      <div className="wp-sargapay-plugin-field-wrap">
        <NumberControl
          onChange={newVal => useUpdateStateSettings("time_wait", newVal)}
          step={1}
          max={48}
          min={1}
          value={
            useSettings && useSettings["time_wait"]
              ? parseInt(useSettings["time_wait"])
              : 24
          }
          label={__("# of hours of waiting for payment", "sargapay")}
        />
      </div>
      <div className="wp-sargapay-plugin-addr-count-container">
        <div className="wp-sargapay-plugin-field-wrap">
          <h2>{__("Unused Addresses Mainnet", "sargapay")}</h2>
          <p style={{fontSize: "25px", textAlign: "center"}}>{useSettings && useSettings["addrs_count"]["mainnet"]}</p>
        </div>
        <div className="wp-sargapay-plugin-field-wrap">
          <h2>{__("Unused Addresses Testnet", "sargapay")}</h2>
          <p style={{fontSize: "25px", textAlign: "center"}}>{useSettings && useSettings["addrs_count"]["testnet"]}</p>
        </div>
      </div>
    </>
  )
}

export default Advanced
