package stream

import (
	"fmt"
	"net/http"
	"sync"
	"time"
)

type Hub struct {
	// Map of fixtureID -> list of client channels
	channels map[int][]chan string
	mutex    sync.Mutex
}

func NewHub() *Hub {
	return &Hub{
		channels: make(map[int][]chan string),
	}
}

func (h *Hub) AddClient(fixtureID int) chan string {
	h.mutex.Lock()
	defer h.mutex.Unlock()

	ch := make(chan string, 10)
	h.channels[fixtureID] = append(h.channels[fixtureID], ch)
	return ch
}

func (h *Hub) RemoveClient(fixtureID int, ch chan string) {
	h.mutex.Lock()
	defer h.mutex.Unlock()

	channels := h.channels[fixtureID]
	for i, c := range channels {
		if c == ch {
			h.channels[fixtureID] = append(channels[:i], channels[i+1:]...)
			close(ch)
			break
		}
	}
}

func (h *Hub) Broadcast(fixtureID int, message string) {
	h.mutex.Lock()
	defer h.mutex.Unlock()

	for _, ch := range h.channels[fixtureID] {
		select {
		case ch <- message:
		default:
			// Client slow or disconnected, skip
		}
	}
}

func (h *Hub) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	// Extract fixtureID from query (simplified)
	fixtureID := 0
	fmt.Sscanf(r.URL.Path, "/stream/%d", &fixtureID)

	if fixtureID == 0 {
		http.Error(w, "Invalid fixture ID", http.StatusBadRequest)
		return
	}

	w.Header().Set("Content-Type", "text/event-stream")
	w.Header().Set("Cache-Control", "no-cache, no-transform")
	w.Header().Set("Connection", "keep-alive")
	w.Header().Set("X-Accel-Buffering", "no")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	flusher, ok := w.(http.Flusher)
	if !ok {
		http.Error(w, "Streaming unsupported", http.StatusInternalServerError)
		return
	}

	clientCh := h.AddClient(fixtureID)
	defer h.RemoveClient(fixtureID, clientCh)

	notify := r.Context().Done()
	heartbeat := time.NewTicker(10 * time.Second)
	defer heartbeat.Stop()

	// Initial heartbeat to flush headers immediately.
	_, _ = fmt.Fprint(w, ": connected\n\n")
	flusher.Flush()

	for {
		select {
		case msg, ok := <-clientCh:
			if !ok {
				return
			}
			if _, err := fmt.Fprintf(w, "data: %s\n\n", msg); err != nil {
				return
			}
			flusher.Flush()
		case <-heartbeat.C:
			// Send heartbeat as data event so browser onmessage updates client-side liveness timer.
			if _, err := fmt.Fprintf(w, "data: {\"type\":\"heartbeat\",\"ts\":%d}\n\n", time.Now().Unix()); err != nil {
				return
			}
			flusher.Flush()
		case <-notify:
			return
		}
	}
}
