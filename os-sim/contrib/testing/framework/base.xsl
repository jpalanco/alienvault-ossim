<?xml version="1.0" encoding="iso-8859-1"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:template match="/">
    <html>
      <body>
        <h2>CPU Info</h2>
        <table border="1">
          <tr bgcolor="#9acd32">
            <th>ID</th>
            <th>Vendor ID</th>
            <th>Family</th>
            <th>Model ID</th>
            <th>Model Name</th>
          </tr>
          <xsl:for-each select="system/cpuInfo/cpu">
            <tr>
              <td>
                <xsl:value-of select="@id" />
              </td>
              <td>
                <xsl:value-of select="@vendor_id" />
              </td>
              <td>
                <xsl:value-of select="@cpu_family" />
              </td>
              <td>
                <xsl:value-of select="@model_id" />
              </td>
              <td>
                <xsl:value-of select="@model_name" />
              </td>
            </tr>
          </xsl:for-each>
        </table>
        <h2>Memory Info</h2>
        <table border="1">
          <tr bgcolor="#9acd32">
            <th>Total</th>
            <th>Free</th>
            <th>Swap Total</th>
            <th>Swap Free</th>
          </tr>
          <xsl:for-each select="system/memInfo">
            <tr>
              <td>
              <xsl:value-of select="@memTotal" />KB</td>
              <td>
              <xsl:value-of select="@memFree" />KB</td>
              <td>
              <xsl:value-of select="@swapTotal" />KB</td>
              <td>
              <xsl:value-of select="@swapFree" />KB</td>
            </tr>
          </xsl:for-each>
        </table>
        <h2>Disk Info</h2>
        <table border="1">
          <tr bgcolor="#9acd32">
            <th>ID</th>
            <th>Valocity</th>
            <th>Size</th>
          </tr>
          <xsl:for-each select="system/diskInfo/disk">
            <tr>
              <td>
                <xsl:value-of select="@id" />
              </td>
              <td>
              <xsl:value-of select="@rVel" />MB/s</td>
              <td>
              <xsl:value-of select="@size" />bytes</td>
            </tr>
          </xsl:for-each>
        </table>
        <h2>PCI Info</h2>
        <table border="1">
          <tr bgcolor="#9acd32">
            <th>Device</th>
            <th>Device Name</th>
            <th>sDevice</th>
            <th>sVendor</th>
            <th>Vendor</th>
            <th>sDevice</th>
            <th>Class</th>
          </tr>
          <xsl:for-each select="system/pciInfo/pciModule">
            <tr>
              <td>
                <xsl:value-of select="@device" />
              </td>
              <td>
                <xsl:value-of select="@deviceName" />
              </td>
              <td>
                <xsl:value-of select="@sDevice" />
              </td>
              <td>
                <xsl:value-of select="@sVendor" />
              </td>
              <td>
                <xsl:value-of select="@vendor" />
              </td>
              <td>
                <xsl:value-of select="@sDevice" />
              </td>
              <td>
                <xsl:value-of select="@dClass" />
              </td>
            </tr>
          </xsl:for-each>
        </table>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
<!--memFree="0" memTotal="0" swapFree="0" swapTotal="0-->
