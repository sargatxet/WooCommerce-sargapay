/*WordPress*/
import {useContext} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import {
    Button,
    Spinner
} from "@wordpress/components";

/*Inbuilt Context*/
import { SettingsContext } from '../../../context/SettingsContext';

const SaveBtn = ({to, title}) => {

    const { useUpdateSettings, useIsPending, useCanSave } = useContext(SettingsContext);

    return (
        <Button
            className="button"
            onClick={() =>
                useUpdateSettings()
            }
            isPrimary
            disabled={useIsPending || !useCanSave}
        >
            {useCanSave?__( 'Save Settings', 'sargapay' ):__( 'Saved', 'sargapay' )}
            {useIsPending ? <Spinner /> : ''}
        </Button>
    );
}

export default SaveBtn;