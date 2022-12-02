import { DataGrid } from "@mui/x-data-grid/DataGrid"
import Stack from "@mui/material/Stack"
/*WordPress*/
import { useContext } from "@wordpress/element"
import { __ } from "@wordpress/i18n"
/*Inbuilt Context*/
import { SettingsContext } from "../../../context/SettingsContext.js"
import Link from "@mui/material/Link"

const Orders = () => {
  const { useSettings } = useContext(SettingsContext)

  const columns = [
    {
      field: "id",
      headerName: "ID",
      width: 70,
      renderCell: data => (
        <Link
          href={`${useSettings.url}/wp-admin/post.php?post=${data.id}&action=edit`}
        >
          #{data.id}
        </Link>
      ),
    },
    { field: "status", headerName: __("Status", "sargapay"), width: 130 },
    { field: "date", headerName: __("Date", "sargapay"), width: 130 },
    {
      field: "price",
      headerName: __("Price ADA", "sargapay"),
      type: "number",
      width: 90,
    },
    {
      field: "currency",
      headerName: __("Currency", "sargapay"),
      width: 90,
    },
    {
      field: "total",
      headerName: __("Total ADA", "sargapay"),
      type: "number",
      width: 90,
    },
    {
      field: "addr",
      headerName: __("Payment Address", "sargapay"),
      width: 450,
    },
  ]

  return (
    <div className="wp-sargapay-plugin-field-wrap">
      <div style={{ height: 400, width: "100%" }}>
        <DataGrid
          rows={useSettings.orders}
          columns={columns}
          pageSize={5}
          rowsPerPageOptions={[5]}
          components={{
            NoRowsOverlay: () => (
              <Stack height="100%" alignItems="center" justifyContent="center">
                {__("No Orders Done With This Payment Gateway", "sargapay")}
              </Stack>
            ),
          }}
        />
      </div>
    </div>
  )
}

export default Orders
