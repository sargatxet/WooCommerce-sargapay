/*WordPress*/
import { useContext, useEffect } from "@wordpress/element"
import { __ } from "@wordpress/i18n"

/*Inbuilt Context*/
import { SettingsContext } from "../../../context/SettingsContext"
import useIsScrolled from "../hooks/useIsScrolled"

/*Inbuilt Components*/
import SettingsNotice from "../molecules/notice"
import Navlist from "../molecules/navlist"
import SaveBtn from "../atoms/save-btn"

const SettingsHeader = () => {
  const { useIsPending, useNotice } = useContext(SettingsContext)
  const isScrolled = useIsScrolled()
  const header_class = "wp-sargapay-plugin-header"
  const header_fixed_class =
    " wp-sargapay-plugin-header-sticky wp-sargapay-plugin-header"

  return (
    <>
      <header className={isScrolled ? header_fixed_class : header_class}>
        <div className="at-flex at-align-items-center at-justify-content-between">
          <div className="wp-sargapay-plugin-title">
            <h1>{__("Sargapay Settings", "sargapay")}</h1>
          </div>
          <div className="wp-sargapay-plugin-button">
            <SaveBtn />
          </div>
        </div>
      </header>
      {useNotice && !useIsPending && <SettingsNotice />}
      <Navlist />
    </>
  )
}

export default SettingsHeader
