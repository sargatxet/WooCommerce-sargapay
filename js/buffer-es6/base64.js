/*
    SargaPay. Cardano gateway plug-in for Woocommerce. 
    Copyright (C) 2021  Sargatxet Pools

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

var lookup = []
var revLookup = []
var Arr = typeof Uint8Array !== "undefined" ? Uint8Array : Array
var inited = false
function init() {
  inited = true
  var code = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/"
  for (var i = 0, len = code.length; i < len; ++i) {
    lookup[i] = code[i]
    revLookup[code.charCodeAt(i)] = i
  }

  revLookup["-".charCodeAt(0)] = 62
  revLookup["_".charCodeAt(0)] = 63
}

export function toByteArray(b64) {
  if (!inited) {
    init()
  }
  var i, j, l, tmp, placeHolders, arr
  var len = b64.length

  if (len % 4 > 0) {
    throw new Error("Invalid string. Length must be a multiple of 4")
  }

  // the number of equal signs (place holders)
  // if there are two placeholders, than the two characters before it
  // represent one byte
  // if there is only one, then the three characters before it represent 2 bytes
  // this is just a cheap hack to not do indexOf twice
  placeHolders = b64[len - 2] === "=" ? 2 : b64[len - 1] === "=" ? 1 : 0

  // base64 is 4/3 + up to two characters of the original data
  arr = new Arr((len * 3) / 4 - placeHolders)

  // if there are placeholders, only get up to the last complete 4 chars
  l = placeHolders > 0 ? len - 4 : len

  var L = 0

  for (i = 0, j = 0; i < l; i += 4, j += 3) {
    tmp =
      (revLookup[b64.charCodeAt(i)] << 18) |
      (revLookup[b64.charCodeAt(i + 1)] << 12) |
      (revLookup[b64.charCodeAt(i + 2)] << 6) |
      revLookup[b64.charCodeAt(i + 3)]
    arr[L++] = (tmp >> 16) & 0xff
    arr[L++] = (tmp >> 8) & 0xff
    arr[L++] = tmp & 0xff
  }

  if (placeHolders === 2) {
    tmp =
      (revLookup[b64.charCodeAt(i)] << 2) |
      (revLookup[b64.charCodeAt(i + 1)] >> 4)
    arr[L++] = tmp & 0xff
  } else if (placeHolders === 1) {
    tmp =
      (revLookup[b64.charCodeAt(i)] << 10) |
      (revLookup[b64.charCodeAt(i + 1)] << 4) |
      (revLookup[b64.charCodeAt(i + 2)] >> 2)
    arr[L++] = (tmp >> 8) & 0xff
    arr[L++] = tmp & 0xff
  }

  return arr
}

function tripletToBase64(num) {
  return (
    lookup[(num >> 18) & 0x3f] +
    lookup[(num >> 12) & 0x3f] +
    lookup[(num >> 6) & 0x3f] +
    lookup[num & 0x3f]
  )
}

function encodeChunk(uint8, start, end) {
  var tmp
  var output = []
  for (var i = start; i < end; i += 3) {
    tmp = (uint8[i] << 16) + (uint8[i + 1] << 8) + uint8[i + 2]
    output.push(tripletToBase64(tmp))
  }
  return output.join("")
}

export function fromByteArray(uint8) {
  if (!inited) {
    init()
  }
  var tmp
  var len = uint8.length
  var extraBytes = len % 3 // if we have 1 byte left, pad 2 bytes
  var output = ""
  var parts = []
  var maxChunkLength = 16383 // must be multiple of 3

  // go through the array every three bytes, we'll deal with trailing stuff later
  for (var i = 0, len2 = len - extraBytes; i < len2; i += maxChunkLength) {
    parts.push(
      encodeChunk(
        uint8,
        i,
        i + maxChunkLength > len2 ? len2 : i + maxChunkLength
      )
    )
  }

  // pad the end with zeros, but make sure to not forget the extra bytes
  if (extraBytes === 1) {
    tmp = uint8[len - 1]
    output += lookup[tmp >> 2]
    output += lookup[(tmp << 4) & 0x3f]
    output += "=="
  } else if (extraBytes === 2) {
    tmp = (uint8[len - 2] << 8) + uint8[len - 1]
    output += lookup[tmp >> 10]
    output += lookup[(tmp >> 4) & 0x3f]
    output += lookup[(tmp << 2) & 0x3f]
    output += "="
  }

  parts.push(output)

  return parts.join("")
}
