import Echo from "laravel-echo"
import Pusher from "pusher-js"

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

window.Pusher = Pusher

const echo = new Echo({
  broadcaster: "reverb",
  key: "wimz1o97fqpynzhhlumr", // Matches your REVERB_APP_KEY
  wsHost: "dealspace-api.nibtun.com", // changed from localhost to your domain
  wsPort: 8080,
  wssPort: 8080,
  forceTLS: true, // set true if SSL is enabled
  enabledTransports: ["ws", "wss"],
  app_id: "552002", // Matches your REVERB_APP_ID
  authEndpoint: 'http://127.0.0.1:8000/api/broadcasting/auth',
  auth: {
    headers: {
      Authorization: `Bearer ${localStorage.getItem("token") || ""}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
  },
})

export default echo
