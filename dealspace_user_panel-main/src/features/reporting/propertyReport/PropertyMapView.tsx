"use client"

import { useCallback, useState, useMemo, useEffect, useRef } from "react"
import { GoogleMap, Marker, useJsApiLoader } from "@react-google-maps/api"
import { Loader, MapPin, X } from "lucide-react"

// Define Google Maps types properly
declare global {
  interface Window {
    google: {
      maps: {
        Map: any
        LatLngBounds: any
        Geocoder: any
        Size: any
        Point: any
        GeocoderResult: any
        GeocoderStatus: any
        OverlayView: any
        LatLng: any
        SymbolPath: any
      }
    }
  }
}

// Type definitions for Google Maps
interface GoogleMapsGeometry {
  location: {
    lat(): number
    lng(): number
  }
}

interface GoogleMapsGeocoderResult {
  geometry: GoogleMapsGeometry
}

type GeocoderStatus =
  | "OK"
  | "ZERO_RESULTS"
  | "OVER_QUERY_LIMIT"
  | "REQUEST_DENIED"
  | "INVALID_REQUEST"
  | "UNKNOWN_ERROR"

interface PropertyMapViewProps {
  data: any[]
  viewMode: "property" | "zipcode"
  selectedProperty: string | null
  onPropertySelect: (property: string | null) => void
  isLoading: boolean
}

interface MarkerData {
  id: string
  position: { lat: number; lng: number }
  title: string
  inquiryCount: number
  isSelected: boolean
  item: any
}

interface PopupPosition {
  x: number
  y: number
}

const libraries: ("places" | "geometry")[] = ["places", "geometry"]

export default function PropertyMapView({
  data,
  viewMode,
  selectedProperty,
  onPropertySelect,
  isLoading,
}: PropertyMapViewProps) {
  const [map, setMap] = useState<any>(null)
  const [activeMarker, setActiveMarker] = useState<string | null>(null)
  const [markers, setMarkers] = useState<MarkerData[]>([])
  const [geocodingInProgress, setGeocodingInProgress] = useState(false)
  const [mapError, setMapError] = useState<string | null>(null)
  const [isScriptLoaded, setIsScriptLoaded] = useState(false)
  const [popupPosition, setPopupPosition] = useState<PopupPosition | null>(null)
  const [hasInitialBounds, setHasInitialBounds] = useState(false) // Track if bounds have been set
  const mapContainerRef = useRef<HTMLDivElement>(null)

  const { isLoaded, loadError } = useJsApiLoader({
    googleMapsApiKey: "AIzaSyBBXWHSFe3JOtodA56rE_CKfhs19SBO1o8",
    libraries,
  })

  const mapContainerStyle = {
    width: "100%",
    height: "400px",
  }

  const [defaultCenter, setDefaultCenter] = useState<{ lat: number; lng: number }>({
    lat: 34.0522,
    lng: -118.2437, // Default to LA
  })

  const mapOptions = useMemo(
    () => ({
      disableDefaultUI: false,
      clickableIcons: false,
      scrollwheel: true,
      mapTypeControl: true,
      streetViewControl: true,
      fullscreenControl: true,
      zoomControl: true,
    }),
    [],
  )

  // Convert lat/lng to pixel coordinates using Google Maps built-in method
  const latLngToPixel = useCallback(
    (lat: number, lng: number) => {
      if (!map) return null

      const overlay = new window.google.maps.OverlayView()
      overlay.setMap(map)

      // Wait for overlay to be ready
      if (!overlay.getProjection()) {
        return null
      }

      const position = new window.google.maps.LatLng(lat, lng)
      const pixel = overlay.getProjection().fromLatLngToContainerPixel(position)

      if (!pixel) return null

      return { x: pixel.x, y: pixel.y }
    },
    [map],
  )

  // Geocode addresses and create markers
  const geocodeAddresses = useCallback(async () => {
    if (!data.length || !window.google || !isScriptLoaded || !map) return

    setGeocodingInProgress(true)
    setMapError(null)
    setMarkers([]) // Clear existing markers

    try {
      const newMarkers: MarkerData[] = []
      const bounds = new window.google.maps.LatLngBounds()
      const geocoder = new window.google.maps.Geocoder()

      for (const item of data) {
        try {
          const address =
            viewMode === "property"
              ? `${item.street}, ${item.city}, ${item.state} ${item.zip_code}`
              : `${item.city}, ${item.state} ${item.zip_code}`

          const results = await new Promise<GoogleMapsGeocoderResult[]>((resolve, reject) => {
            geocoder.geocode({ address }, (results: GoogleMapsGeocoderResult[] | null, status: GeocoderStatus) => {
              if (status === "OK" && results) {
                resolve(results)
              } else {
                reject(new Error(`Geocoding failed for ${address}: ${status}`))
              }
            })
          })

          if (results[0]) {
            const position = {
              lat: results[0].geometry.location.lat(),
              lng: results[0].geometry.location.lng(),
            }

            const markerId = viewMode === "property" ? item.mls_number : item.zip_code
            const inquiryCount = item.total_events || 0
            const isSelected = selectedProperty === markerId

            newMarkers.push({
              id: markerId,
              position,
              title: viewMode === "property" ? item.street : item.zip_code,
              inquiryCount,
              isSelected,
              item,
            })

            bounds.extend(position)
          }

          // Add small delay to avoid rate limiting
          await new Promise((resolve) => setTimeout(resolve, 100))
        } catch (error) {
          console.warn(`Failed to geocode address for item:`, item, error)
        }
      }

      setMarkers(newMarkers)

      // Fit map to show all markers with appropriate zoom
      if (newMarkers.length > 0 && !hasInitialBounds) {
        setTimeout(() => {
          map.fitBounds(bounds, { padding: 50 }) // Add padding for better view
          
          // Set a maximum zoom level to ensure it's not too close
          setTimeout(() => {
            const currentZoom = map.getZoom()
            if (currentZoom > 12) { // Limit maximum zoom to 12 for wider view
              map.setZoom(12)
            }
          }, 100)
          
          setHasInitialBounds(true)
        }, 500) // Reduced delay
        setDefaultCenter({
          lat: bounds.getCenter().lat(),
          lng: bounds.getCenter().lng(),
        })
      }
    } catch (error) {
      console.error("Error during geocoding:", error)
      setMapError("Failed to load map locations")
    } finally {
      setGeocodingInProgress(false)
    }
  }, [data, viewMode, selectedProperty, map, isScriptLoaded, hasInitialBounds])

  const onLoad = useCallback((map: any) => {
    setMap(map)
  }, [])

  const onUnmount = useCallback(() => {
    setMap(null)
  }, [])

  // Handle script load
  const handleScriptLoad = useCallback(() => {
    setIsScriptLoaded(true)
  }, [])

  // Reset bounds tracking when data changes
  useEffect(() => {
    setHasInitialBounds(false)
  }, [data, viewMode])

  // Geocode when script is loaded and data is available
  useEffect(() => {
    if (isScriptLoaded && map && data.length > 0) {
      geocodeAddresses()
    }
  }, [isScriptLoaded, map, data, viewMode])

  // Update markers when selection changes
  const updatedMarkers = useMemo(() => {
    return markers.map((marker) => ({
      ...marker,
      isSelected: selectedProperty === marker.id,
    }))
  }, [markers, selectedProperty])

  const handleMarkerClick = useCallback(
    (markerId: string, position: { lat: number; lng: number }) => {
      // Only update popup and active marker state, don't trigger external selection
      if (activeMarker === markerId) {
        setActiveMarker(null)
        setPopupPosition(null)
      } else {
        setActiveMarker(markerId)

        // Simple center positioning - popup appears in center of map
        if (mapContainerRef.current) {
          const containerRect = mapContainerRef.current.getBoundingClientRect()
          setPopupPosition({
            x: containerRect.width / 2,
            y: containerRect.height / 2 - 50, // Slightly above center
          })
        }
        setDefaultCenter({
          lat: position.lat,
          lng: position.lng,
        })
      }
      
      // Only call onPropertySelect if you want the parent to know about the selection
      // Comment this out if you don't want it to trigger parent re-renders
      // onPropertySelect(markerId)
    },
    [activeMarker], // Removed onPropertySelect from dependencies
  )

  // Close popup when map is clicked
  const handleMapClick = useCallback(() => {
    setActiveMarker(null)
    setPopupPosition(null)
  }, [])

  // Create custom marker icon with inquiry count
  const getMarkerIcon = useCallback((inquiryCount: number, isSelected: boolean) => {
    // Check if Google Maps API is fully loaded
    if (!window.google?.maps?.Size || !window.google?.maps?.Point) {
      // Return a simple colored marker if API isn't ready
      return {
        path: window.google?.maps?.SymbolPath?.CIRCLE || 0,
        scale: 8,
        fillColor: isSelected ? "#3B82F6" : "#EF4444",
        fillOpacity: 0.8,
        strokeColor: "#FFFFFF",
        strokeWeight: 2,
      }
    }

    try {
      // Create a custom marker with text
      const canvas = document.createElement("canvas")
      const context = canvas.getContext("2d")
      if (!context) {
        // Fallback to simple marker
        return {
          path: window.google.maps.SymbolPath.CIRCLE,
          scale: 8,
          fillColor: isSelected ? "#3B82F6" : "#EF4444",
          fillOpacity: 0.8,
          strokeColor: "#FFFFFF",
          strokeWeight: 2,
        }
      }

      canvas.width = 40
      canvas.height = 40

      // Draw circle
      context.beginPath()
      context.arc(20, 20, 18, 0, 2 * Math.PI)
      context.fillStyle = isSelected ? "#3B82F6" : "#EF4444"
      context.fill()
      context.strokeStyle = "#FFFFFF"
      context.lineWidth = 3
      context.stroke()

      // Draw text
      context.fillStyle = "#FFFFFF"
      context.font = "bold 12px Arial"
      context.textAlign = "center"
      context.textBaseline = "middle"
      context.fillText(inquiryCount.toString(), 20, 20)

      return {
        url: canvas.toDataURL(),
        scaledSize: new window.google.maps.Size(40, 40),
        anchor: new window.google.maps.Point(20, 20),
      }
    } catch (error) {
      console.warn("Failed to create custom marker icon:", error)
      // Fallback to simple marker
      return {
        path: window.google.maps.SymbolPath.CIRCLE,
        scale: 8,
        fillColor: isSelected ? "#3B82F6" : "#EF4444",
        fillOpacity: 0.8,
        strokeColor: "#FFFFFF",
        strokeWeight: 2,
      }
    }
  }, [])

  // Get active marker data safely
  const activeMarkerData = useMemo(() => {
    return activeMarker ? updatedMarkers.find((m) => m.id === activeMarker) : null
  }, [activeMarker, updatedMarkers])

  useEffect(() => {
    if (isLoaded) {
      setIsScriptLoaded(true)
    }
  }, [isLoaded])

  if (isLoading) {
    return (
      <div className="bg-white rounded-lg shadow h-96 flex items-center justify-center">
        <div className="flex items-center gap-2 text-gray-500">
          <Loader className="w-5 h-5 animate-spin" />
          Loading map data...
        </div>
      </div>
    )
  }

  if (mapError) {
    return (
      <div className="bg-white rounded-lg shadow h-96 flex items-center justify-center">
        <div className="text-center">
          <MapPin className="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p className="text-red-600 mb-2">{mapError}</p>
          <button
            onClick={() => {
              setMapError(null)
              if (map && data.length > 0) {
                geocodeAddresses()
              }
            }}
            className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            Retry
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="bg-white rounded-lg shadow overflow-hidden">
      <div className="p-4 border-b flex items-center justify-between">
        <h3 className="font-semibold text-gray-900">{viewMode === "property" ? "Property" : "Zip Code"} Inquiry Map</h3>
        <div className="flex items-center gap-4 text-xs text-gray-500">
          {geocodingInProgress && (
            <div className="flex items-center gap-1">
              <Loader className="w-3 h-3 animate-spin" />
              Loading locations...
            </div>
          )}
        </div>
      </div>

      <div className="relative" ref={mapContainerRef}>
        <GoogleMap
          mapContainerStyle={mapContainerStyle}
          center={defaultCenter}
          zoom={10}
          onLoad={onLoad}
          onUnmount={onUnmount}
          options={mapOptions}
          onClick={handleMapClick}
        >
          {updatedMarkers.map((marker) => (
            <Marker
              key={marker.id}
              position={marker.position}
              onClick={() => handleMarkerClick(marker.id, marker.position)}
              icon={getMarkerIcon(marker.inquiryCount, marker.isSelected)}
            />
          ))}
        </GoogleMap>

        {/* Custom Popup */}
        {activeMarker && activeMarkerData && popupPosition && (
          <div
            className="absolute z-10 bg-white rounded-lg shadow-lg border border-gray-200 p-4 max-w-xs"
            style={{
              left: `${popupPosition.x}px`,
              top: `${popupPosition.y}px`,
              transform: "translate(-50%, -50%)", // Center the popup
            }}
          >
            <button
              onClick={() => {
                setActiveMarker(null)
                setPopupPosition(null)
              }}
              className="absolute top-2 right-2 text-gray-400 hover:text-gray-600"
            >
              <X className="w-4 h-4" />
            </button>

            <div className="pr-6">
              <div className="font-semibold text-gray-900 mb-1">
                {viewMode === "property" ? activeMarkerData.item.street : activeMarkerData.item.zip_code}
              </div>

              <div className="text-sm text-gray-600 mb-2">
                {viewMode === "property"
                  ? `${activeMarkerData.item.city}, ${activeMarkerData.item.state} ${activeMarkerData.item.zip_code}`
                  : `${activeMarkerData.item.city}, ${activeMarkerData.item.state}`}
              </div>

              {viewMode === "property" && activeMarkerData.item.price && (
                <div className="text-sm text-green-600 font-semibold mb-2">
                  ${Number.parseInt(activeMarkerData.item.price).toLocaleString()}
                </div>
              )}

              <div className="text-xs text-gray-500">
                {activeMarkerData.item.total_events || 0} inquiries •{" "}
                {viewMode === "property"
                  ? `${activeMarkerData.item.unique_leads || 0} leads`
                  : `${activeMarkerData.item.unique_properties || 0} properties`}
              </div>
            </div>
          </div>
        )}

        {data.length === 0 && !isLoading && (
          <div className="absolute inset-0 flex items-center justify-center bg-gray-50 bg-opacity-75">
            <p className="text-gray-500">No properties to display on map</p>
          </div>
        )}
      </div>
    </div>
  )
}