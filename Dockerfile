# Stage 1: Builder - Build the Next.js application
FROM node:20-alpine AS builder

# Set working directory
WORKDIR /app

# Copy package files and install dependencies
COPY package*.json ./
RUN npm install

# Copy the rest of the source code
COPY . .

# Build the application
RUN npm run build

# Stage 2: Runner - Create the final, lightweight image
FROM node:20-alpine AS runner
WORKDIR /app

# Set environment variables
ENV NODE_ENV=production
ENV PORT=3001

# Copy the standalone output from the builder stage
# This includes the server.js file needed to run the app
COPY --from=builder /app/.next/standalone ./

# Copy the public and static folders
COPY --from=builder /app/.next/static ./.next/static

# Next.js with a basePath needs the public folder to be served.
# Although this project doesn't have one, it's good practice.
COPY --from=builder /app/public ./public

# Expose the port the app runs on
EXPOSE 3001

# The standalone output creates a server.js file.
# The command to start the app is `node server.js`
CMD ["node", "server.js"]
