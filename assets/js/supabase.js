// Supabase client configuration
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

// Get Supabase credentials from environment or use placeholders
const supabaseUrl = import.meta.env?.VITE_SUPABASE_URL || 'https://placeholder.supabase.co'
const supabaseKey = import.meta.env?.VITE_SUPABASE_ANON_KEY || 'placeholder-key'

export const supabase = createClient(supabaseUrl, supabaseKey)
export const supabaseAdmin = createClient(supabaseUrl, supabaseKey)

// Check if Supabase is properly configured
export function isSupabaseConfigured() {
  return !supabaseUrl.includes('placeholder') && !supabaseKey.includes('placeholder')
}


export { supabaseUrl, supabaseKey }