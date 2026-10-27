import { http } from '@/http/client'

/** Reviews API (backend: Domain/Review + Orchestrator/Review). snake_case only here. */

export interface BarberReview {
  id: number
  bookingRequestId: number
  rating: number
  comment: string | null
  firstName: string
  /** "YYYY-MM-DD" in the barbershop's timezone. */
  date: string
  hiddenAt: string | null
  featuredPosition: number | null
}

interface BarberReviewBody {
  id: number
  booking_request_id: number
  rating: number
  comment: string | null
  first_name: string
  date: string
  hidden_at: string | null
  featured_position: number | null
}

const toReview = (body: BarberReviewBody): BarberReview => ({
  id: body.id,
  bookingRequestId: body.booking_request_id,
  rating: body.rating,
  comment: body.comment,
  firstName: body.first_name,
  date: body.date,
  hiddenAt: body.hidden_at,
  featuredPosition: body.featured_position,
})

export function formatAverage(average: number): string {
  return average.toFixed(1).replace('.', ',')
}

export async function submitReview(appointmentId: number, rating: number, comment: string): Promise<void> {
  await http('/client/appointments/' + appointmentId + '/review', {
    method: 'POST',
    auth: 'client',
    body: { rating, comment: comment.trim() || null },
  })
}

export async function fetchReviews(): Promise<BarberReview[]> {
  const { data } = await http<BarberReviewBody[]>('/reviews')
  return data.map(toReview)
}

export async function hideReview(id: number): Promise<BarberReview> {
  const { data } = await http<BarberReviewBody>(`/reviews/${id}/hide`, { method: 'POST' })
  return toReview(data)
}

export async function featureReviews(ids: number[]): Promise<void> {
  await http('/reviews/featured', { method: 'PUT', body: { review_ids: ids } })
}
