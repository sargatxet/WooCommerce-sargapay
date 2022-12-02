/*WordPress*/
import { __ } from "@wordpress/i18n"

/*Reducer for Settings*/
const SettingsReducer = (state, action) => {
    let newState = Object.assign({}, state)

    switch (action.type) {
        case "FETCH_SETTINGS":
            newState.fetchedSettings = action.payload.fetchedSettings
            newState.stateSettings = action.payload.stateSettings
            newState.isPending = false
            newState.canSave = false

            if (
                typeof action.payload.fetchedSettings
                .sargapay_settings_fetch_settings_errors !==
                "undefined"
            ) {
                newState.notice = __("An error occurred.", "sargapay")
                newState.hasError = true
            }
            break

        case "UPDATE_SETTINGS_BEFORE":
            newState.isPending = action.payload.isPending
            break

        case "UPDATE_SETTINGS":
            newState.fetchedSettings = action.payload.fetchedSettings
            newState.stateSettings = action.payload.stateSettings
            newState.isPending = false

            let canSave = false,
                notice = __("Saved Successfully.", "sargapay"),
                hasError = false
            if (
                typeof action.payload.fetchedSettings
                .error_msg !== "undefined"
            ) {
                canSave = true
                notice = action.payload.fetchedSettings.error_msg
                hasError = true
            }

            newState.canSave = canSave
            newState.notice = notice
            newState.hasError = hasError
            break

        case "UPDATE_STATE":
            if (action.payload.fetchedSettings) {
                newState.fetchedSettings = action.payload.fetchedSettings
            }
            if (action.payload.stateSettings) {
                newState.stateSettings = action.payload.stateSettings
            }
            if (typeof action.payload.isPending !== "undefined") {
                newState.isPending = action.payload.isPending
            }
            if (typeof action.payload.notice !== "undefined") {
                newState.notice = action.payload.notice
            }
            if (typeof action.payload.hasError !== "undefined") {
                newState.hasError = action.payload.hasError
            }

            if (typeof action.payload.canSave !== "undefined") {
                newState.canSave = action.payload.canSave
            }
            break
    }
    return newState
}
export default SettingsReducer