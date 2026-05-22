FROM golang:1.21-alpine

WORKDIR /app

RUN apk add --no-cache gcc musl-dev

COPY v2/worker-go/go.mod v2/worker-go/go.sum* ./
RUN go mod download

COPY v2/worker-go/ ./

RUN go build -o worker main.go

CMD ["./worker"]
