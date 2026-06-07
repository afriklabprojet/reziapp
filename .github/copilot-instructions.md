# ReziApp - Laravel Project Instructions

## Project Overview
ReziApp is a SaaS platform for geolocated furnished residence search in Abidjan, Côte d'Ivoire.

## Tech Stack
- **Backend**: Laravel (latest stable)
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js
- **Database**: MySQL with geospatial indexing
- **Maps**: Google Maps or Mapbox API
- **Authentication**: Laravel Breeze/Sanctum

## Project Structure
- User-facing features: geolocation search, map view, property details
- Owner features: property management, dashboard, photo uploads
- Admin features: moderation, user management, statistics

## Development Guidelines
- Always use absolute paths for file references
- Follow Laravel naming conventions
- Use Eloquent relationships for data associations
- Implement proper validation on all forms
- Use middleware for authentication and authorization
- Optimize images and implement lazy loading
- Cache geolocation queries for performance
- Keep response times under 2 seconds

## Database Conventions
- Use migrations for all schema changes
- Add spatial indexes for latitude/longitude columns
- Use proper foreign key constraints
- Implement soft deletes where appropriate

## Security Requirements
- HTTPS only in production
- CSRF protection on all forms
- SQL injection prevention via Eloquent
- File upload validation
- Rate limiting on API endpoints
- Admin approval for new listings

## Code Style
- Follow PSR-12 coding standards
- Use meaningful variable and method names in French or English
- Comment complex geospatial calculations
- Keep controllers thin, use service classes for business logic
