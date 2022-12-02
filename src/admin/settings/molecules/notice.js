/*WordPress*/
import {useContext, useEffect} from "@wordpress/element";
import {Notice, Popover} from "@wordpress/components";

/*Inbuilt Context*/
import { SettingsContext } from '../../../context/SettingsContext.js';

const SettingsNotice = () => {
    const { useNotice, useHasError, useUpdateState } = useContext(SettingsContext);

    function removeNotice(){
        useUpdateState({
            notice : '',
            hasError : false,
        })
    }

    useEffect(() => {
        const timer = setTimeout(() => {
            removeNotice();
        }, 5000);
        return () => clearTimeout(timer);
    }, []);

    return (
        <Popover
            className="wp-sargapay-plugin-popover"
        >
            <Notice
                className="wp-sargapay-plugin-notice"
                onRemove={() => removeNotice()}
                status={useHasError?'error':'success'}
            >
                <p>
                    {useNotice}
                </p>
            </Notice>
        </Popover>
    )
};

export default SettingsNotice;