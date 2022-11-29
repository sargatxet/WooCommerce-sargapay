/*WordPress*/
import { useContext, useState } from "@wordpress/element"
import { __ } from "@wordpress/i18n"
import { TextControl, Button, Icon, Tip } from "@wordpress/components"
import { C } from "lucid-cardano"
import { bech32 } from "bech32"
/*Inbuilt Context*/
import { SettingsContext } from "../../../context/SettingsContext.js"

// TODO: validate settings before saving

const Keys = () => {
  const { useSettings, useUpdateStateSettings } = useContext(SettingsContext)
  const [payAddresses, setPayAddresses] = useState({
    mainnet: "",
    testnet: "",
    index: 0,
  })
  const [invalidKey, setInvalidKey] = useState(false)
  const [lock, setLock] = useState({
    mkp: true,
    main_block: true,
    test_block: true,
  })

  const generatePaymentAddress = (index, mpk) => {
    setInvalidKey(false)
    let xpub_bech = null
    try {
      // public key yoroi format
      xpub_bech = C.Bip32PublicKey.from_bytes(
        Buffer.from(mpk, "hex")
      ).to_bech32()
    } catch (error) {
      // public key xpub format
      if (mpk.startsWith("xpub")) {
        xpub_bech = mpk
      } else {
        /* decode the public key using bech32 and encoded with
         * xpub header to be used in cardano-serialization-lib */
        try {
          const decode_xpub = bech32.decode(mpk, 150)
          xpub_bech = bech32.encode("xpub", decode_xpub.words, 150)
        } catch (error) {
          xpub_bech = null
          console.dir(error)
          // show error
          setInvalidKey(true)
        }
      }
    }

    if (xpub_bech !== null) {
      const init_index = parseInt(index)
      const accountKey = C.Bip32PublicKey.from_bech32(xpub_bech)

      const utxoPubKey = accountKey.derive(0).derive(init_index)
      // Generate Stake Key
      const stakeKey = accountKey.derive(2).derive(init_index)
      // Generate Enterprise Address
      const testnet = C.BaseAddress.new(
        0,
        C.StakeCredential.from_keyhash(utxoPubKey.to_raw_key().hash()),
        C.StakeCredential.from_keyhash(stakeKey.to_raw_key().hash())
      )
      const mainnet = C.BaseAddress.new(
        1,
        C.StakeCredential.from_keyhash(utxoPubKey.to_raw_key().hash()),
        C.StakeCredential.from_keyhash(stakeKey.to_raw_key().hash())
      )
      setPayAddresses({
        ...payAddresses,
        testnet: testnet.to_address().to_bech32(),
        mainnet: mainnet.to_address().to_bech32(),
      })
    }
  }

  const updateIndex = newVal => {
    if (!isNaN(newVal)) {
      Math.round(newVal) >= 0 &&
        setPayAddresses({ ...payAddresses, index: Math.round(newVal) })
    }
  }

  return (
    <>
      <div className="wp-sargapay-plugin-field-wrap">
        <div style={{ display: "flex", justifyContent: "flex-end" }}>
          <Icon
            style={{ cursor: "pointer" }}
            onClick={() => setLock({ ...lock, mkp: !lock.mkp })}
            icon={lock.mkp ? "lock" : "unlock"}
          />
        </div>
        <TextControl
          label={__("Public Master Key", "sargapay")}
          help={__(
            "Place the Public Address Key to generate Payment Addresses.",
            "sargapay"
          )}
          type={lock.mkp ? "password" : "text"}
          placeholder={__("Public Master Key", "sargapay")}
          value={useSettings && useSettings["mpk"]}
          onChange={newVal => {
            useUpdateStateSettings("mpk", newVal)
            setInvalidKey(false)
          }}
        />
        <div className="wp-sargapay-plugin-testmpk">
          <TextControl
            className="wp-sargapay-plugin-input-number"
            label={__("Select Index", "sargapay")}
            placeholder={__("Select Index", "sargapay")}
            value={payAddresses.index}
            type="number"
            onKeyUp={newVal => updateIndex(newVal)}
            onChange={newVal => updateIndex(newVal)}
            min="0"
            step="1"
          />
          <Button
            variant="primary"
            onClick={() =>
              generatePaymentAddress(payAddresses.index, useSettings["mpk"])
            }
          >
            Test Public Key
          </Button>
        </div>
        {invalidKey ? (
          <div>
            <p className="wp-sargapay-plugin-addrs">
              {__("Invalid Public Key", "sargapay")}
            </p>
          </div>
        ) : (
          <div>
            <p className="wp-sargapay-plugin-addrs">
              {payAddresses.mainnet && (
                <span style={{ fontWeight: "bold" }}>Mainnet: </span>
              )}
              {`${payAddresses.mainnet}`}
            </p>
            <p className="wp-sargapay-plugin-addrs">
              {payAddresses.testnet && (
                <span style={{ fontWeight: "bold" }}>Testnet: </span>
              )}
              {`${payAddresses.testnet}`}
            </p>
          </div>
        )}
      </div>
      <div className="wp-sargapay-plugin-field-wrap">
        <div style={{ display: "flex", justifyContent: "flex-end" }}>
          <Icon
            style={{ cursor: "pointer" }}
            onClick={() => setLock({ ...lock, main_block: !lock.main_block })}
            icon={lock.main_block ? "lock" : "unlock"}
          />
        </div>
        <TextControl
          label={__("Blockfrost Key", "sargapay")}
          placeholder={__("Blockfrost Key", "sargapay")}
          value={useSettings && useSettings["blockfrost_key"]}
          type={lock.main_block ? "password" : "text"}
          onChange={newVal => useUpdateStateSettings("blockfrost_key", newVal)}
          help={__("Place your Mainnet Api Key from Blockfrost", "sargapay")}
        />
        <Tip>
          <a href="https://blockfrost.io/" rel="nofollow" target="_blank">
            BlockFrost
          </a>
        </Tip>
      </div>
      <div className="wp-sargapay-plugin-field-wrap">
        <div style={{ display: "flex", justifyContent: "flex-end" }}>
          <Icon
            style={{ cursor: "pointer" }}
            onClick={() => setLock({ ...lock, test_block: !lock.test_block })}
            icon={lock.test_block ? "lock" : "unlock"}
          />
        </div>
        <TextControl
          label={__("Blockfrost Testnet Key", "sargapay")}
          placeholder={__("Blockfrost Testnet Key", "sargapay")}
          value={useSettings && useSettings["blockfrost_test_key"]}
          type={lock.test_block ? "password" : "text"}
          onChange={newVal =>
            useUpdateStateSettings("blockfrost_test_key", newVal)
          }
          help={__("Place your Testnet Api Key from Blockfrost", "sargapay")}
        />
        <Tip>
          <a href="https://blockfrost.io/" rel="nofollow" target="_blank">
            BlockFrost
          </a>
        </Tip>
      </div>
    </>
  )
}

export default Keys
