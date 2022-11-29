/**
 * SCSS
 */
import "./style.scss"

/*WordPress*/
import { render, useContext } from "@wordpress/element"

import { Spinner } from "@wordpress/components"

/*Inbuilt Context Provider*/
import SettingsContextProvider, {
  SettingsContext,
} from "../../context/SettingsContext.js"

/*Router*/
import { HashRouter, Route, Routes, Navigate } from "react-router-dom"

/*Inbuilt Components*/
import { General, Advanced, Keys, Orders } from "./pages"
import { SettingsHeader, SettingsFooter } from "./organisms"

const SettingRouters = () => {
  const { useSettings } = useContext(SettingsContext)

  if (!Object.keys(useSettings).length) {
    return <Spinner className="wp-sargapay-plugin-page-loader" />
  }
  return (
    <>
      <div className="wp-sargapay-plugin">
        <SettingsHeader />
        <main className="wp-sargapay-plugin-main">
          <Routes>
            <Route exact path="/general" element={<General />} />
            <Route exact path="/keys" element={<Keys />} />
            <Route exact path="/advanced" element={<Advanced />} />
            <Route exact path="/orders" element={<Orders />} />

            <Route path="/" element={<Navigate replace to={"/general"} />} />
          </Routes>
        </main>
        <SettingsFooter />
      </div>
    </>
  )
}

const InitSettings = () => {
  return (
    <HashRouter basename="/">
      <SettingsContextProvider>
        <SettingRouters />
      </SettingsContextProvider>
    </HashRouter>
  )
}

const renderReact = () => {
  if (
    "undefined" !==
      typeof document.getElementById(wpSargapayPluginBuild.root_id) &&
    null !== document.getElementById(wpSargapayPluginBuild.root_id)
  ) {
    render(
      <InitSettings />,
      document.getElementById(wpSargapayPluginBuild.root_id)
    )
  } else {
    console.log("undefinded div")
  }
}

if (document.readyState !== "loading") {
  renderReact()
} else {
  document.addEventListener("DOMContentLoaded", () => renderReact())
}
