/*WordPress*/
import React from "react"
import { useContext } from "@wordpress/element"
import { __ } from "@wordpress/i18n"
import {
  TextControl,
  BaseControl,
  ToggleControl,
  SelectControl,
} from "@wordpress/components"

/*Inbuilt Context*/
import { SettingsContext } from "../../../context/SettingsContext.js"

const General = () => {
  const { useSettings, useUpdateStateSettings } = useContext(SettingsContext)


  return (
    <>
      <div className="wp-sargapay-plugin-multifield-wrap">
        <BaseControl
          label={__("Enable Plugin", "sargapay")}
          id="enabled_control__spp"
        >
          <ToggleControl
            label={__("Check to Enable Sargapay Gateway", "sargapay")}
            checked={
              useSettings &&
              useSettings["enabled"] &&
              (useSettings["enabled"].localeCompare("yes") === 0 ||
                useSettings["enabled"] == 1)
            }
            onChange={() => {
              useUpdateStateSettings(
                "enabled",
                useSettings &&
                  useSettings["enabled"] &&
                  useSettings["enabled"].localeCompare("yes") === 0
                  ? "no"
                  : "yes"
              )
            }}
          />
        </BaseControl>
        <BaseControl
          label={__("Enable Light Wallets", "sargapay")}
          id="lightWallets_control__spp"
        >
          <ToggleControl
            label={__("Check to Enable Light Wallets Buttons", "sargapay")}
            checked={
              useSettings &&
              useSettings["lightWallets"] &&
              (useSettings["lightWallets"].localeCompare("yes") === 0 ||
                useSettings["lightWallets"] == 1)
            }
            onChange={() => {
              useUpdateStateSettings(
                "lightWallets",
                useSettings &&
                  useSettings["lightWallets"] &&
                  useSettings["lightWallets"].localeCompare("yes") === 0
                  ? "no"
                  : "yes"
              )
            }}
          />
        </BaseControl>
        <BaseControl
          label={__("Enable Testnet Mode", "sargapay")}
          id="testmode_control__spp"
        >
          <ToggleControl
            label={__("Check to Enable Testmode", "sargapay")}
            checked={
              useSettings &&
              useSettings["testmode"] &&
              (useSettings["testmode"].localeCompare("yes") === 0 ||
                useSettings["testmode"] == 1)
            }
            onChange={() => {
              useUpdateStateSettings(
                "testmode",
                useSettings &&
                  useSettings["testmode"] &&
                  useSettings["testmode"].localeCompare("yes") === 0
                  ? "no"
                  : "yes"
              )
            }}
          />
        </BaseControl>
      </div>
      <div className="wp-sargapay-plugin-field-wrap">
        <TextControl
          label={__("Title", "sargapay")}
          placeholder={__("Title showing on checkout", "sargapay")}
          value={useSettings && useSettings["title"]}
          onChange={newVal => useUpdateStateSettings("title", newVal)}
        />
      </div>
      <div className="wp-sargapay-plugin-field-wrap">
        <TextControl
          label={__("Description", "sargapay")}
          placeholder={__("Description show on checkout", "sargapay")}
          value={useSettings && useSettings["description"]}
          onChange={newVal => useUpdateStateSettings("description", newVal)}
        />
      </div>
      <div className="wp-sargapay-plugin-multifield-wrap">
        <SelectControl
          label={__(
            "Confirmations needed to valid payment",
            "sargapay"
          )}
          value={useSettings && useSettings["confirmations"]}
          options={[
            {
              label: "1",
              value: "1",
            },
            {
              label: "2",
              value: "2",
            },
            {
              label: "3",
              value: "3",
            },
            {
              label: "4",
              value: "4",
            },
            {
              label: "5",
              value: "5",
            },
            {
              label: "6",
              value: "6",
            },
            {
              label: "7",
              value: "7",
            },
            {
              label: "8",
              value: "8",
            },
            {
              label: "9",
              value: "9",
            },
            {
              label: "10",
              value: "10",
            },
            {
              label: "20",
              value: "20",
            },
            {
              label: "30",
              value: "30",
            },
            {
              label: "40",
              value: "40",
            },
            {
              label: "50",
              value: "50",
            },
          ]}
          onChange={newVal => useUpdateStateSettings("confirmations", newVal)}
        />
        <SelectControl
          label={__("Default Fiat Currency", "sargapay")}
          value={useSettings && useSettings["currency"]}
          options={[
            {
              label: "$ USD",
              value: "USD",
            },
            {
              label: "€ EUR",
              value: "EUR",
            },
          ]}
          onChange={newVal => useUpdateStateSettings("currency", newVal)}
        />
      </div>
    </>
  )
}

export default General
