# Dockerfile

# 1. Builder Stage
FROM node:20-slim AS builder
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY . .
RUN npm run build

# 2. Runner Stage
FROM node:20-slim AS runner
WORKDIR /app

# Set up non-root user
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copy standalone output
COPY --from=builder /app/.next/standalone ./
# Copy static assets
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# Expose port and set user
EXPOSE 3000
USER nextjs

# Start the app with environment variable debugging
# This command will first print all environment variables visible to the container,
# then start the Next.js application. This is the definitive test to see if
# the --env-file flag is working as expected.
CMD ["sh", "-c", "echo '--- [QREASY_DOCKER_DEBUG] Printing environment variables at container startup ---' && printenv && echo '--- Starting Next.js app ---' && exec node server.js"]
